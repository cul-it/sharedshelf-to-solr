<?php
// sharedshelf-to-solr - update all sharedshelf collections in solr

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');

try {
  // sharedshelf user
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password']);

  // collection list and start time
  $task = parse_ini_file("sharedshelf-to-solr.ini");
  if ($task === FALSE) {
    throw new Exception("Need sharedshelf-to-solr.ini", 1);
  }

  $start_date = $task['start_date'];
  foreach($task['config'] as $config) {
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }
    print_r($project);
  }

  print_r($task);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

