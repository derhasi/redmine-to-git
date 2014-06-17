<?php

namespace derhasi\RedmineToGit;

/**
 * Class WikiIndex
 *
 * Representing page index of a redmine project.
 */
class WikiIndex {

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
  public function __construct(array $data) {

    foreach ($data as $item) {
      $page = new WikiPage($item);
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
   * Helper to sort data array by name.
   */
  protected function sort() {
    ksort($this->data);
  }

  /**
   * Load index object from file.
   *
   * @param string $filepath
   *
   * @return WikiIndex
   */
  public static function loadFromJSONFile($filepath) {
    if (!file_exists($filepath)) {
      $data = array();
    }
    else {
      $str = file_get_contents($filepath);
      $data = json_decode($str);
    }

    return new WikiIndex($data);
  }

  /**
   * Save index to file as JSON.
   *
   * @param string $filepath
   */
  public function saveToJSONFile($filepath) {
    // Make sure index is sorted.
    $this->sort();
    $json = json_encode(array_values($this->data), JSON_PRETTY_PRINT);
    file_put_contents($filepath, $json);
  }

}
