<?php

namespace derhasi\RedmineToGit;

class WikiPageVersion {

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
   * @var string
   */
  var $text;

  /**
   * @var array
   */
  var $author;

  /**
   * @var string
   */
  var $comments;

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
    $this->author = $data->author;
    $this->text = $data->text;
    $this->comments = $data->comments;

    if (isset($data->parent)) {
      $this->parent = (object) $data->parent;
    }
  }

  /**
   * Create a page object from the current version.
   *
   * @return WikiPage
   */
  public function createPage() {

    // Add/Update page properties for the given keys.
    $keys = array(
      'title', 'created_on', 'updated_on', 'version', 'parent'
    );

    $data = array();
    foreach ($keys as $key) {
      if (isset($this->{$key})) {
        $data[$key] = $this->{$key};
      }
    }

    return new WikiPage($data);
  }

  /**
   * @param WikiPage $page
   */
  public function updatePage($page) {

    // Add/Update page properties for the given keys.
    $keys = array(
      'title', 'created_on', 'updated_on', 'version', 'parent'
    );

    foreach ($keys as $key) {
      if (isset($this->{$key})) {
        $page->{$key} = $this->{$key};
      }
      else {
        unset($page->{$key});
      }
    }
  }

}
