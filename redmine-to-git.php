#!/usr/bin/env php
<?php

/**
 * @file
 * Provides command line tool for writing content of redmine to a git repo.
 */

// Handling autoloading for different use cases.
// @see https://github.com/sebastianbergmann/phpunit/blob/master/phpunit
foreach (array(__DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
  if (file_exists($file)) {
    define('DERHASI_REDMINE_TO_GIT_COMPOSER_INSTALL', $file);
    break;
  }
}

unset($file);

// Provide warning, when
if (!defined('DERHASI_REDMINE_TO_GIT_COMPOSER_INSTALL')) {
  fwrite(STDERR,
    'You need to set up the project dependencies using the following commands:' . PHP_EOL .
    'wget http://getcomposer.org/composer.phar' . PHP_EOL .
    'php composer.phar install' . PHP_EOL
  );
  die(1);
}

require DERHASI_REDMINE_TO_GIT_COMPOSER_INSTALL;

// Run the command line tool.
use \derhasi\RedmineToGit\Command\WikiCommand;
use \Symfony\Component\Console\Application;

$application = new Application();
$application->add(new WikiCommand());
$application->run();
