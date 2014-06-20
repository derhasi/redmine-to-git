<?php

namespace derhasi\RedmineToGit\Command;

use \derhasi\RedmineToGit\Git;
use \derhasi\RedmineToGit\RedmineConnection;
use \derhasi\RedmineToGit\Project;
use \derhasi\RedmineToGit\WikiIndex;
use \derhasi\RedmineToGit\WikiPage;
use \derhasi\RedmineToGit\WikiPageVersion;
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
   * @var string
   */
  var $repo;

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
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get our necessary arguments from the input.
    $redmine = $input->getArgument('redmine');
    $apikey = $input->getArgument('apikey');
    $project = $input->getArgument('project');
    $this->repo = $input->getArgument('repo');

    // Init redmine client and get wiki pages information.
    $this->redmine = new RedmineConnection($redmine, $apikey);
    $this->project = new Project($this->redmine, $project);

    $success = $this->initGit($input, $output);
    if ($success === FALSE) return;

    $this->wikiPages = $this->project->loadWikiPages();
    if (empty($this->wikiPages)) {
      $output->writeln('<info>There are no wiki pages in the project.</info>');
      return;
    }

    // Get the wiki index status from the file stored in the repo.
    $this->wikiIndex = WikiIndex::loadFromJSONFile($this->project, $this->repo . '/index.json');

    // Calculating the wiki page, versions, that are needed to be added to the
    // repo.
    $this->buildWikiVersions($input, $output);

    // And now there is the git part.
    $this->updateGitRepo($input, $output);
  }


  /**
   * Helper to initialie the repo.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @return bool
   */
  protected function initGit(InputInterface $input, OutputInterface $output) {
    // Init repo.
    $this->git = new \derhasi\RedmineToGit\Git();
    // @todo: option to init repo.
    $this->git->setRepository($this->repo);

    // Validate repo, by checking status.
    try {
      $this->git->status();
    }
      // When there is a git excpetion we are likely to have no repo there.
    catch (\PHPGit\Exception\GitException $e) {
      $output->writeln("<error>{$this->repo} is no valid git repo.</error>");
      return FALSE;
    }
  }

  /**
   * Helper to fill the wiki versions array.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function buildWikiVersions(InputInterface $input, OutputInterface $output) {

    $this->wikiVersions = array();

    // Show a progress bar for featching wiki page information.
    $output->writeln('<info>Fetching wiki page information from API ...</info>');
    $progress = $this->getHelperSet()->get('progress');
    $progress->start($output, count($this->wikiPages));

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
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function updateGitRepo(InputInterface $input, OutputInterface $output) {

    $output->writeln("<info>Committing changes ...</info>");

    $changes = FALSE;

    // @todo: option to stash current repo changes?
    foreach ($this->wikiVersions as $version) {

      // Add / update file in working directory
      $filename = $version->title . '.textile';
      $filepath = $this->repo . '/' . $version->title . '.textile';
      file_put_contents($filepath, $version->text);

      // Add commit message with author information and correct date
      $this->git->add($filename);

      // Update index on each commit.
      $this->wikiIndex->updateWithVersion($version);
      $this->wikiIndex->saveToJSONFile($this->repo . '/index.json');
      $this->git->add('index.json');

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

        // @todo: check status before committing, to avoid empty commits.
        $this->git->commit($message, array(
          'author' => $version->author->getGitAuthorName(),
          'date' => $version->updated_on,
        ));

        // Write status.
        $output->writeln("<comment>$message</comment>");
      }

      // @todo: handling comments?
      // @todo: handling documents?

    }

    if (empty($changes)) {
      $output->writeln("<comment>There were no changes to commit.</comment>");
    }
  }

}
