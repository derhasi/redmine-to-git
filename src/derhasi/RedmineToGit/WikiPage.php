<?php

namespace derhasi\RedmineToGit;

class WikiPage {

  /**
   * @var string
   */
  var $title;

  /**
   * @var array
   */
  var $parent;

  /**
   * @var integer
   */
  var $version;

  /**
   * @var string
   */
  var $created_on;

  /**
   * @var string
   */
  var $updated_on;

  /**
   * Constructor for the WikiPage object.
   *
   * @param array|object $data
   */
  public function __construct($data) {

    if (is_array($data)) {
      $data = (object) $data;
    }

    $this->title = $data->title;
    $this->version = $data->version;
    $this->created_on = $data->created_on;
    $this->updated_on = $data->updated_on;

    if (isset($data->parent)) {
      $this->parent = (object) $data->parent;
    }
  }
}
