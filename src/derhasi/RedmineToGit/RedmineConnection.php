<?php

namespace derhasi\RedmineToGit;

class RedmineConnection {

  /**
   * @var \Redmine\Client
   */
  var $client;

  /**
   * @var string
   */
  var $url;

  /**
   * @var string
   */
  var $apikey;

  /**
   * @var array
   */
  var $users;

  /**
   * @param string $url
   * @param string $apikey
   */
  public function __construct($url, $apikey) {
    $this->url = $url;
    $this->apikey = $apikey;
    $this->client = new \Redmine\Client($url, $apikey);
  }

  /**
   * Load user data from API.
   *
   * @param $id
   */
  public function loadUser($id, $reset = FALSE) {
    if (!isset($this->users[$id]) || $reset) {
      $user = $this->client->api('user')->show($id);

      if (!empty($user['user'])) {
        $this->users[$id] = new User($this, $user['user']);
      }
    }

    return $this->users[$id];
  }


}
