#!/usr/bin/env php
<?php

// This file is generated by Composer
require_once 'vendor/autoload.php';

use \derhasi\RedmineToGit\Command\WikiCommand;
use \Symfony\Component\Console\Application;

$application = new Application();
$application->add(new WikiCommand());
$application->run();