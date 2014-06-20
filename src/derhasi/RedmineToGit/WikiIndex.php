<?php

namespace derhasi\RedmineToGit;

/**
 * Class WikiIndex
 *
 * Representing page index of a redmine project.
 */
class WikiIndex implements \JsonSerializable {

  /**
   * @var Project
   */
  var $project;

  /**
   * @var array
   */
  protected $original_data = array();

  /**
   * @var array
   */
  protected $data = array();

  /**
   * Constructor.
   *
   * @param array $data
   *   Array representing the index data as returned from the redmine API.
   */

  /**
   * @param Project $project
   * @param array $data
   */
  public function __construct(Project $project, array $data) {

    $this->project = $project;

    foreach ($data as $item) {
      $page = new WikiPage($project, $item);
      $this->original_data[$page->title] = $page;
      $this->data[$page->title] = clone $page;
    }
  }

  /**
   * Updated index list with page verions information.
   *
   * @param \derhasi\RedmineToGit\WikiPageVersion $version
   */
  public function updateWithVersion(WikiPageVersion $version) {

    if (isset($this->data[$version->title])) {
      $version->updatePage($this->data[$version->title]);
    }
    else {
      $this->data[$version->title] = $version->createPage();
    }
  }

  /**
   * Get the version ID of the given page in the given index.
   *
   * @param WikiPage $page
   *
   * @return int
   */
  public function getVersionID(WikiPage $page) {
    if (isset($this->data[$page->title])) {
      return $this->data[$page->title]->version;
    }
    return 0;
  }

  /**
   * Helper to sort data array by name.
   */
  protected function sort() {
    ksort($this->data);
  }

  /**
   * Load index object from file.
   *
   * param Project $project
   * @param string $filepath
   *
   * @return WikiIndex
   */
  public static function loadFromJSONFile(Project $project, $filepath) {
    if (!file_exists($filepath)) {
      $data = array();
    }
    else {
      $str = file_get_contents($filepath);
      $data = json_decode($str);
    }

    return new WikiIndex($project, $data);
  }

  /**
   * Save index to file as JSON.
   *
   * @param string $filepath
   */
  public function saveToJSONFile($filepath) {
    // Make sure index is sorted.
    $this->sort();
    $data = array_values($this->data);
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($filepath, $json);
  }

  /**
   * Implements JsonSerializable::jsonSerialize().
   */
  public function jsonSerialize() {
    return $this->data;
  }

}
