<?php

namespace derhasi\RedmineToGit;

use \Eloquent\Pathogen\Path;

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
   * @param Project $project
   * @param array $data
   *   Array representing the index data as returned from the redmine API.
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
   * @param Project $project
   * @param \Eloquent\Pathogen\AbsolutePathInterface $base_path
   *
   * @return WikiIndex
   */
  public static function loadFromFile(Project $project, $base_path) {
    $data = array();

    // Get the wiki file path object relative to the
    $indexFile = $base_path->resolve(
      Path::fromString("index.wiki.json")
    );

    if (file_exists($indexFile->string())) {
      $str = file_get_contents($indexFile->string());
      $raw_data = (object) json_decode($str);

      if (isset($raw_data->data)) {
        $data = $raw_data->data;
      }

      // Throw error if the project does not match the stored project.
      if (isset($raw_data->project) && $raw_data->project != $project->project) {
        throw new \ErrorException('The given project does not match the index project');
      }

      // Throw error if the project does not match the stored project.
      if (isset($raw_data->redmine) && $raw_data->redmine != $project->redmine->client->getUrl()) {
        throw new \ErrorException('The given redmine URLs do not match for the given index.');
      }
    }

    return new WikiIndex($project, $data);
  }

  /**
   * Write representation of the page version to given
   *
   * @param \Eloquent\Pathogen\AbsolutePathInterface $base_path
   *
   * @return \Eloquent\Pathogen\AbsolutePathInterface[]
   *   Array of path objects that have been changed.
   */
  public function writeFile($base_path) {

    // Get the wiki file path object relative to the
    $indexFile = $base_path->resolve(
      Path::fromString("index.wiki.json")
    );

    // Make sure the path exists.
    $dir = dirname($indexFile->string());
    if (!is_dir($dir)) {
      mkdir($dir, 0777, TRUE);
    }

    // Write the json to the index File.
    $json = json_encode($this, JSON_PRETTY_PRINT);
    file_put_contents($indexFile->string(), $json);

    return array(
      $indexFile,
    );
  }

  /**
   * Implements JsonSerializable::jsonSerialize().
   */
  public function jsonSerialize() {
    // Make sure index is sorted.
    $this->sort();
    return array(
      'redmine' => $this->project->redmine->client->getUrl(),
      'project' => $this->project->project,
      'data' => array_values($this->data),
    );
  }

}
