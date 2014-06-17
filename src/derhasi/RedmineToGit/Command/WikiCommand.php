<?php

namespace derhasi\RedmineToGit\Command;

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
   * @var \Redmine\Client
   */
  var $redmineClient;

  /**
   * @var string
   */
  var $project;

  /**
   * @var string
   */
  var $repo;

  /**
   * @var \PHPGit\Git;
   */
  var $git;

  /**
   * @var array
   */
  var $wikiPages = array();

  /**
   * @var array
   */
  var $wikiVersions = array();

  /**
   * @var array
   */
  var $wikiUsers = array();

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
    $this->project = $input->getArgument('project');
    $this->repo = $input->getArgument('repo');

    // Init redmine client and get wiki pages information.
    $this->redmineClient = new \Redmine\Client($redmine, $apikey);

    $success = $this->initGit($input, $output);
    if ($success === FALSE) return;

    $success = $this->buildWikiPages($input, $output);
    if ($success === FALSE) return;

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
    $git = new \PHPGit\Git();
    $git->setRepository($this->repo);

    // Validate repo, by checking status.
    try {
      $git->status();
    }
      // When there is a git excpetion we are likely to have no repo there.
    catch (\PHPGit\Exception\GitException $e) {
      $output->writeln("<error>{$this->repo} is no valid git repo.</error>");
      return FALSE;
    }
  }

  /**
   * Helper to build wiki pages array.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @return bool
   */
  protected function buildWikiPages(InputInterface $input, OutputInterface $output) {
    $wiki_pages = $this->redmineClient->api('wiki')->all($this->project);

    if (empty($wiki_pages['wiki_pages'])) {
      $output->writeln('<info>There are no wiki pages in the project.</info>');
      return FALSE;
    }
    else {
      $this->wikiPages = $wiki_pages['wiki_pages'];
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
    print $output->writeln('<info>Fetching wiki page information from API ...</info>');
    $progress = $this->getHelperSet()->get('progress');
    $progress->start($output, count($this->wikiPages));

    foreach ($this->wikiPages as $pid => $page) {

      $current_version = $page['version'];
      for ($version = $current_version; $version > 0; $version--) {

        $full_page = $this->redmineClient->api('wiki')->show($this->project, $page['title'], $version);
        // When we got a valid wiki page, we add it to the versions array, keyed by
        // date.
        if (isset($full_page['wiki_page']) && $full_page['wiki_page']['version'] == $version) {
          $key = $full_page['wiki_page']['updated_on'] . '--' . $pid . '--' . $version;
          $this->wikiVersions[$key] = $full_page['wiki_page'];

          // Get the full author object.
          $uid = $full_page['wiki_page']['author']['id'];
          if (!isset($this->wikiUsers[$uid])) {
            $user = $this->redmineClient->api('user')->show($uid);
            if (!empty($user['user'])) {
              $this->wikiUsers[$uid] = $user['user'];
            }
          }
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

    foreach ($this->wikiVersions as $vid => $version) {

      // @todo: Add / update file in working directory
      // @todo: Add commit message with author information and correct date
      // @todo: handling comments?

    }

  }

}
