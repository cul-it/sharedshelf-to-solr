<?php
// sharedshelf-to-solr - update all sharedshelf collections in solr

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

$log = FALSE;

try {

  // batch process information
  $task = parse_ini_file("sharedshelf-to-solr.ini", TRUE);
  if ($task === FALSE) {
    echo "Need sharedshelf-to-solr.ini\n";
    exit (1);
  }

  // open log
  if (empty($task['process']['log_file_prefix'])) {
    echo "Need log_file_prefix\n";
    exit (1);
  }

  $log = new SharedShelfToSolrLogger($task['process']['log_file_prefix']);

  $log->task('ssUser');
  // sharedshelf user
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  if (!isset($task['process']['cookie_jar_path'])) {
    throw new Exception("Expecting cookie_jar_path in .ini file", 1);
  }

  $log->task('SharedShelfService');
  $ss = new SharedShelfService($user['email'], $user['password'], $task['process']['cookie_jar_path']);

  $start_date = $task['process']['start_date'];
  foreach($task['configuration_files']['config'] as $config) {
    $log->task($config);
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

