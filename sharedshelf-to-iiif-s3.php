<?php
// sharedshelf-to-iiif-s3.php - move sharedshelf collection into static iiif on s3


require_once('SharedShelfService.php');
require_once('SharedShelfToSolrLogger.php');
require_once('image-to-iiif-s3.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [--force] [-p NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--force - ignore timestamps and rewrite all solr records" . PHP_EOL;
  echo "-p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php" . PHP_EOL;
  exit (0);
}

$log = FALSE;

$options = getopt("p:",array("help", "force"));

if (isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
if (isset($options['p'])) {
  if (is_numeric($options['p'])) {
    $single_collection = $options['p'];
  }
  else {
    usage();
  }
}
else {
  $single_collection = FALSE;
}

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

  foreach($task['configuration_files']['config'] as $config) {
    $log->task($config);
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }
    if ($single_collection !== FALSE) {
      if ($project['project'] != $single_collection) {
        echo PHP_EOL . "Skipping collection " . $project['project'] . " as it was not selected on the command line" . PHP_EOL;
        continue;
      }
    }

    $log->note('project_asset_ids');
    $project_id = $project['project'];
    $asset_count = $ss->project_assets_count($project_id);
    $log->note("asset_count:$asset_count");
    echo "$config asset count: $asset_count\n";
    $per_page = 25;
    for ($start = 0; $start < $asset_count; $start += $per_page) {
      $assets =  $ss->project_assets($project_id, $start, $per_page);
      $counter = $start;
      foreach ($assets as $asset) {
        $ss_id = $asset['id'];
        $log->item("asset $ss_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        $url = $ss->media_url($ss_id);

        $s3_path = "$project_id/$ss_id";

        image_to_iiif_s3($url, $s3_path, $force_replacement);

        throw new Exception("shortcut to exit", 1);

      }
    }
  }

  print_r($task);
  $log->task('Done.');
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