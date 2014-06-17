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
    $repo = $input->getArgument('repo');


    // Init repo.
    $git = new \PHPGit\Git();
    $git->setRepository($repo);

    // Validate repo, by checking status.
    try {
      $git->status();
    }
    // When there is a git excpetion we are likely to have no repo there.
    catch (\PHPGit\Exception\GitException $e) {
      $output->writeln("<error>{$repo} is no valid git repo.</error>");
      return;
    }

    // Init redmine client and get wiki pages information.
    $client = new \Redmine\Client($redmine, $apikey);
    $wiki_pages = $client->api('wiki')->all($project);

    if (empty($wiki_pages['wiki_pages'])) {
      $output->writeln('<info>There are no wiki pages in the project.</info>');
      return;
    }

    $versions = array();
    // Temp array to collect user information.
    $users = array();

    foreach ($wiki_pages['wiki_pages'] as $pid => $page) {

      $current_version = $page['version'];
      for ($version = $current_version; $version > 0; $version--) {
        print $output->writeln($page['title'] . $version);

        $full_page = $client->api('wiki')->show($project, $page['title'], $version);
        // When we got a valid wiki page, we add it to the versions array, keyed by
        // date.
        if (isset($full_page['wiki_page']) && $full_page['wiki_page']['version'] == $version) {
          $key = $full_page['wiki_page']['updated_on'] . '--' . $pid . '--' . $version;
          $versions[$key] = $full_page['wiki_page'];

          // Get the full author object.
          $uid = $full_page['wiki_page']['author']['id'];
          if (!isset($users[$uid])) {
            $user = $client->api('user')->show($uid);
            if (!empty($user['user'])) {
              $users[$uid] = $user['user'];
            }
          }
        }
      }
    }

    // Sort versions by update date, as this is the key.
    ksort($versions);

    // And now there is the git part.

    //print_r($all_users);

  }
}
