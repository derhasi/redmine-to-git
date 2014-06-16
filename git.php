<?php

// Initialize the library. If the path to the Git binary is not passed as
// the first argument when instantiating GitWrapper, it is auto-discovered.
require_once 'vendor/autoload.php';

$path = '../testgit2';

$git = new PHPGit\Git();
$git->setRepository($path);


$status = $git->status();
print_r($status);

// @todo: check if file can be added before adding it
$git->add('test.txt');

print_r($status);

// Only commit the change, if there is any for the given file.
if (!empty($status['changes'])) {

  $git->commit('Adds test.txt');

  exit;
}


