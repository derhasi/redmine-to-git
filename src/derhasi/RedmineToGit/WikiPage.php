<?php

namespace derhasi\RedmineToGit;

class WikiPage implements \JsonSerializable {

  /**
   * @var Project
   */
  var $project;

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
  public function __construct(Project $project, $data) {

    $this->project = $project;

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


  /**
   * Load a range of versions from the page.
   *
   * @param $from
   * @param $to
   *
   * @return WikiPageVersion[]
   */
  public function getVersions($from, $to) {

    $versions = array();
    for ($i = $from; $i <= $to; $i++) {

      $version = $this->loadVersion($i);
      if ($version) {
        $versions[$i] = $version;
      }
    }
    return $versions;
  }

  /**
   * Load version object from a given page.
   *
   * @param WikiPage $page
   * @param $version_id
   *
   * @return WikiPageVersion
   */
  public function loadVersion($version_id) {
    $version = $this
      ->project
      ->redmine
      ->client
      ->api('wiki')
      ->show($this->project->project, $this->title, $version_id);

    if (isset($version['wiki_page']) && $version['wiki_page']['version'] == $version_id) {
      return new WikiPageVersion($this->project, $version['wiki_page']);
    }
  }

  /**
   * Implements JsonSerializable::jsonSerialize().
   */
  public function jsonSerialize() {

    $return = array(
      'title' => $this->title,
      'version' => $this->version,
      'created_on' => $this->created_on,
      'updated_on' => $this->updated_on,
    );

    if (isset($this->parent)) {
      $return['parent'] = $this->parent;
    }

    return $return;
  }

}
