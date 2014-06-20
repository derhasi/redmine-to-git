<?php

namespace derhasi\RedmineToGit;

class Project {

  /**
   * @var RedmineConnection
   */
  var $redmine;

  /**
   * @var string
   */
  var $project;

  /**
   * @param RedmineConnection $redmine
   * @param string $project
   */
  public function __construct(RedmineConnection $redmine, $project) {
    $this->redmine = $redmine;
    $this->project = $project;
  }

  /**
   * Load all project wiki pages.
   *
   * @return WikiPage[]
   */
  public function loadWikiPages() {
    $return = array();
    $wiki_pages = $this->redmine->client->api('wiki')->all($this->project);

    if (!empty($wiki_pages['wiki_pages'])) {
      foreach ($wiki_pages['wiki_pages'] as $page) {
        $return[] = new WikiPage($this, $page);
      }
    }

    return $return;
  }

}
