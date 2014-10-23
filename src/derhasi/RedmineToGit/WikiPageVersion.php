<?php

namespace derhasi\RedmineToGit;

use \GuzzleHttp\Client;
use \GuzzleHttp\Stream\Stream;
use \Eloquent\Pathogen\Path;

class WikiPageVersion {

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
   * @var string
   */
  var $text;

  /**
   * @var User
   */
  var $author;

  /**
   * @var string
   */
  var $comments;

  /**
   * @var array
   */
  var $attachments = array();

  /**
   * Constructor for the WikiPage object.
   *
   * @param Project $project
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
    $this->text = $data->text;
    $this->comments = $data->comments;

    if (isset($data->attachments)) {
      $this->attachments = $data->attachments;
    }

    // Load the user object.
    $this->author = $this->project->redmine->loadUser($data->author['id']);

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
      'title', 'created_on', 'updated_on', 'version', 'parent', 'attachments',
    );

    $data = array();
    foreach ($keys as $key) {
      if (isset($this->{$key})) {
        $data[$key] = $this->{$key};
      }
    }

    return new WikiPage($this->project, $data);
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


  /**
   * Write representation of the page version to given
   *
   * @param \Eloquent\Pathogen\AbsolutePathInterface $base_path
   *
   * @return \Eloquent\Pathogen\AbsolutePathInterface[]
   *   Array of path objects
   */
  public function writeFile($base_path) {

    // Get the wiki file path object relative to the base path.
    $wikiFile = $base_path->resolve(
      Path::fromString("{$this->title}.wiki.textile")
    );

    // Make sure the path exists.
    $dir = dirname($wikiFile->string());
    if (!is_dir($dir)) {
      mkdir($dir, 0777, TRUE);
    }

    // Add / update file in working directory with version content.
    file_put_contents($wikiFile->string(), $this->text);

    return array(
      $wikiFile,
    );
  }

  /**
   * Writes attachments to the local storage.
   *
   * @param \Eloquent\Pathogen\AbsolutePathInterface $base_path
   * @param int $max_file_size
   * @param \Symfony\Component\Console\Output\OutputInterface $output;
   * @param \Symfony\Component\Console\Helper\ProgressHelper $progress;
   *
   * @return \Eloquent\Pathogen\AbsolutePathInterface[]
   *   Array of path objects
   */
  public function writeAttachments($base_path, $max_file_size, $output, $progress) {
    $files = array();

    // Quit if we got no attachments at all.
    if (empty($this->attachments)) {
      return array();
    }
    // First check the files we really need to download.
    $attachments = array();

    $attachments_folder = $base_path->resolve(Path::fromString('attachments'));
    foreach ($this->attachments as $attachment) {
      $attachment_local_path = $attachments_folder->resolve(Path::fromString($attachment['id'] . '-' . $attachment['filename']));

      // As Redmine cannot change uploaded files, we do not try to download
      // those again.
      // Additionally we ignore files that are too big.
      if ( ($max_file_size == 0 || $attachment['filesize'] <= $max_file_size)
        && !file_exists($attachment_local_path->string())) {
        $attachments[$attachment['content_url']] = $attachment_local_path;
      }
    }

    // Quit of we got no new attachments.
    if (empty($attachments)) {
      return array();
    }

    $output->writeln("<comment>Downloading attachments for {$this->title} ...</comment>");
    $progress->start($output, count($attachments));
    $progress->advance(0);

    $client = new Client([
      'base_url' => $this->project->redmine->url,
      'defaults' => [
        'auth' => [$this->project->redmine->apikey, 'password'],
      ]
    ]);

    // Make sure the attachments folder is created.
    if (!file_exists($attachments_folder->string())) {
      @mkdir($attachments_folder->string());
    }

    foreach ($attachments as $attachment_url => $attachment_local_path) {

      $save_stream = Stream::factory(fopen($attachment_local_path->string(), 'w'));
      $client->get($attachment_url,
        ['save_to' => $save_stream]
      );

      // Add file to the changed files, so they can get commited.
      $files[] = $attachment_local_path;

      $progress->advance();
    }

    $progress->finish();

    return $files;
  }
}
