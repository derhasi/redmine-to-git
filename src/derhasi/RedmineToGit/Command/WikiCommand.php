<?php

namespace derhasi\RedmineToGit\Command;

use \derhasi\RedmineToGit\Git;
use \derhasi\RedmineToGit\RedmineConnection;
use \derhasi\RedmineToGit\Project;
use \derhasi\RedmineToGit\WikiIndex;
use \derhasi\RedmineToGit\WikiPage;
use \derhasi\RedmineToGit\WikiPageVersion;
use \Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony command implementation for converting redmine wikipages to git.
 */
class WikiCommand extends Command
{

  /**
   * @var \derhasi\RedmineToGit\RedmineConnection
   */
  var $redmine;

  /**
   * @var \derhasi\RedmineToGit\Project
   */
  var $project;

  /**
   * @var \Eloquent\Pathogen\AbsolutePathInterface
   */
  var $repoPath;

  /**
   * @var \Eloquent\Pathogen\AbsolutePathInterface
   */
  var $workingPath;

  /**
   * @var \derhasi\RedmineToGit\Git
   */
  var $git;

  /**
   * @var WikiIndex
   */
  var $wikiIndex;

  /**
   * @var WikiPage[]
   */
  var $wikiPages = array();

  /**
   * @var array
   */
  var $wikiVersions = array();

  /**
   * @var \Symfony\Component\Console\Input\InputInterface;
   */
  var $currentInput;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface;
   */
  var $currentOutput;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('wiki')
      ->setDescription('Converts wiki pages of a redmine project to git')
      ->addArgument(
        'redmine',
        InputArgument::REQUIRED,
        'Provide the URL for the redmine installation'
      )
      ->addArgument(
        'apikey',
        InputArgument::REQUIRED,
        'The APIKey for accessing the redmine API'
      )
      ->addArgument(
        'project',
        InputArgument::REQUIRED,
        'The project name'
      )
      ->addArgument(
        'repo',
        InputArgument::REQUIRED,
        'The path to the git repo working directory'
      )
      ->addOption(
        'subdir',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Subdirectory within the git repo to store the wiki pages',
        ''
      )
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->currentInput = $input;
    $this->currentOutput = $output;

    // Get our necessary arguments from the input.
    $redmine = $this->currentInput->getArgument('redmine');
    $apikey = $this->currentInput->getArgument('apikey');
    $project = $this->currentInput->getArgument('project');

    $this->currentOutput->writeln("<info>PROJECT: $project</info>");
    $this->currentOutput->writeln('<info>' . str_repeat("=", strlen($project) + 9) . '</info>');

    // Init redmine client and get wiki pages information.
    $this->redmine = new RedmineConnection($redmine, $apikey);
    $this->project = new Project($this->redmine, $project);

    $this->initDirectories();

    $success = $this->initGit();
    if ($success === FALSE) return;

    $this->wikiPages = $this->project->loadWikiPages();
    if (empty($this->wikiPages)) {
      $this->currentOutput->writeln('<info>There are no wiki pages in the project.</info>');
      return;
    }

    // Get the wiki index status from the file stored in the repo.
    $this->wikiIndex = WikiIndex::loadFromFile($this->project, $this->workingPath);

    // Calculating the wiki page, versions, that are needed to be added to the
    // repo.
    $this->buildWikiVersions();

    // And now there is the git part.
    $this->updateGitRepo();
  }

  /**
   * Helper to initialize the directory variables.
   *
   * @throws \ErrorException
   */
  protected function initDirectories() {
    // Pathogen working directory as reference for initial path calculation.
    $factory = new PlatformFileSystemPathFactory;
    $workingDirectoryPath = $factory->createWorkingDirectoryPath();

    // Build repo root path object, relative to the current working directory.
    $repo_path_input = $this->currentInput->getArgument('repo');
    $this->repoPath = $workingDirectoryPath->resolve(
      $factory->create($repo_path_input)
    );

    // Get the new working directory from the --subdir option.
    $subdir_input = $this->currentInput->getOption('subdir');
    $this->workingPath = $this->repoPath->resolve(
      $factory->create($subdir_input)
    );

    // If the working directory is not in or is not the repository's directory
    // we cannot proceed.
    if (!$this->repoPath->isAncestorOf($this->workingPath) && $this->repoPath->string() != $this->workingPath->string()) {
      throw new \ErrorException('The subdirectory has to be located in the repository');
    }

  }

  /**
   * Helper to initialie the repo.
   *
   * @return bool
   */
  protected function initGit() {
    // Init repo.
    $this->git = new \derhasi\RedmineToGit\Git();
    // @todo: option to init repo.
    $this->git->setRepository($this->repoPath->string());

    // Validate repo, by checking status.
    try {
      $this->git->status();
    }
      // When there is a git excpetion we are likely to have no repo there.
    catch (\PHPGit\Exception\GitException $e) {
      $this->currentOutput->writeln("<error>{$this->repoPath->string()} is no valid git repo.</error>");
      return FALSE;
    }
  }

  /**
   * Helper to fill the wiki versions array.
   */
  protected function buildWikiVersions() {

    $this->wikiVersions = array();

    // Show a progress bar for featching wiki page information.
    $this->currentOutput->writeln("<info>Fetching wiki page information from API ...</info>");
    $progress = $this->getHelperSet()->get('progress');
    $progress->start($this->currentOutput, count($this->wikiPages));

    foreach ($this->wikiPages as $page) {

      $current_version = $page->version;
      $index_version = $this->wikiIndex->getVersionID($page);

      // Only get Versions, that are not in the current git index.
      if ($current_version > $index_version) {
        $versions = $page->getVersions($index_version + 1, $current_version);
        foreach ($versions as $version) {
          $key = $version->updated_on . '-' . $version->title . '-' . $version->version;
          $this->wikiVersions[$key] = $version;
        }
      }

      // advances the progress bar 1 unit
      $progress->advance();
    }

    // Sort versions by update date, as this is the key.
    ksort($this->wikiVersions);

    $progress->finish();
  }

  /**
   * Helper to write the version information to the git repo.
   */
  protected function updateGitRepo() {

    $this->currentOutput->writeln("<info>Committing changes ...</info>");

    $changes = FALSE;

    // @todo: option to stash current repo changes?
    foreach ($this->wikiVersions as $version) {

      // Perform any changes in the filesystem for the given version.
      $this->updateFilesForVersion($version);

      // Only really commit if there are changes to commit.
      if ($this->git->hasStagedChanges()) {
        $changes++;

        // Build commit message.
        if ($version->version == 1) {
          $message = "Created page {$version->title} by {$version->author->name}";
        }
        else {
          $message = "Updated page {$version->title} by {$version->author->name}";
        }

        $commit_message = "{$this->project->project}: $message";
        $commit_message .= "\n Date: {$version->updated_on}";

        // If the version got some comments, we add that to the commit message.
        if (!empty($version->comments)) {
          $commit_message .= "\n Message: {$version->comments}";
        }

        $this->git->commit($commit_message, array(
          'author' => $version->author->getGitAuthorName(),
          'date' => $version->updated_on,
        ));

        // Write status.
        $this->currentOutput->writeln("<comment>$message</comment>");
      }

      // @todo: handling comments?
      // @todo: handling documents?

    }

    if (empty($changes)) {
      $this->currentOutput->writeln("<comment>There were no changes to commit.</comment>");
    }
  }

  /**
   * Update files for the given version.
   *
   * @param WikiPageVersion $version
   */
  protected function updateFilesForVersion($version) {

    // Update some files.
    $version_files = $version->writeFile($this->workingPath);

    // Update index on each commit.
    $this->wikiIndex->updateWithVersion($version);
    $index_files = $this->wikiIndex->writeFile($this->workingPath);

    // Add commit message with author information and correct date
    foreach (array_merge($version_files, $index_files) as $file) {
      $this->git->add(
        $file->relativeTo($this->repoPath)->string()
      );
    }

  }
}
