<?php

namespace derhasi\RedmineToGit;

/**
 * Class Git.
 *
 * Some additional funcitonality for \PHPGit\Git().
 */
class Git extends \PHPGit\Git {

  /**
   * Checks if the current git status has staged changes.
   */
  public function hasStagedChanges() {

    $status = $this->status();

    $staged_statuses = array(
      \PHPGit\Command\StatusCommand::ADDED,
      \PHPGit\Command\StatusCommand::DELETED,
      \PHPGit\Command\StatusCommand::COPIED,
      \PHPGit\Command\StatusCommand::MODIFIED,
    );

    // If we got no changes we got no staged changes.
    if (empty($status['changes'])) {
      return FALSE;
    }

    // Check if any status indicates a staged change.
    foreach ($status['changes'] as $change) {
      if (in_array($change['index'], $staged_statuses)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
