<?php

namespace derhasi\RedmineToGit;

class User {

  /**
   * @var \Redmine\Client
   */
  var $redmine;

  /**
   * @var string
   */
  var $name;

  /**
   * @var integer
   */
  var $id;

  /**
   * @var string
   */
  var $mail;

  /**
   * Constructor for the WikiPage object.
   *
   * @param array|object $data
   */
  public function __construct(RedmineConnection $redmine, $data) {

    $this->redmine = $redmine;

    if (is_array($data)) {
      $data = (object) $data;
    }

    $this->id = $data->id;
    $this->name = $data->firstname . ' ' . $data->lastname;
    $this->mail = $data->mail;
  }

  /**
   * Get the name to provide for the author.
   *
   * @return string
   */
  public function getGitAuthorName() {
    return "{$this->name} <{$this->mail}>";
  }

}
