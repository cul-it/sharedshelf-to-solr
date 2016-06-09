<?php
// testURL.php

require_once('SharedShelfService.php');
require_once('SharedShelfToSolrLogger.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [-a NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "-a - process SharedShelf asset ID NNN (NNN must be numeric)" . PHP_EOL;
  exit (0);
}

$log = FALSE;

try {

  $options = getopt("a:",array("help"));

  if ($options === false || isset($options['help'])) {
    usage();
  }
  $ss_id = 3317772;
  if (isset($options['a'])) {
    if (is_numeric($options['a'])) {
      $ss_id = $options['a'];
    }
    else {
      usage();
    }
  }

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

  $ss_id = 3317772;
  echo $url, "\n";
  $iiif_info = $ss->media_iiif_info($ss_id);
  print_r($iiif_info);

  $url = $ss->media_url($ss_id);
  print_r($url);
  echo $url, "\n";
  $iiif_info = $ss->media_iiif_info($ss_id);
  print_r($iiif_info);
  echo "\n\n";
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  if ($log !== FALSE) {
    $log->error($error);
  }
  else {
    echo $error;
  }
  exit (1);
}
exit (0);

