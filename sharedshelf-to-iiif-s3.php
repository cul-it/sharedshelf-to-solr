<?php
// sharedshelf-to-iiif-s3.php - move sharedshelf collection into static iiif on s3


require_once('SharedShelfService.php');
require_once('SharedShelfToSolrLogger.php');
require_once('image-to-iiif-s3.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [--force] [-p NNN] [-s NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--force - ignore timestamps and rewrite all solr records" . PHP_EOL;
  echo "-p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php" . PHP_EOL;
  echo "-s - only process one of the images in the collection - id NNN" . PHP_EOL;;
  exit (0);
}

$log = FALSE;

$options = getopt("p:s:",array("help", "force", "one"));

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
if (isset($options['s'])) {
  if (is_numeric($options['s'])) {
    $single_item = $options['s'];
  }
  else {
    usage();
  }
}
else {
  $single_item = FALSE;
}

try {

  $supported_image_formats = array('png', 'jpg', 'gif', 'tif');

  // batch process information
  $task = parse_ini_file("sharedshelf-to-iiif-s3.ini", TRUE);
  if ($task === FALSE) {
    echo "Need sharedshelf-to-iiif-s3.ini\n";
    exit (1);
  }

  // open log
  if (empty($task['process']['log_file_prefix'])) {
    echo "Need log_file_prefix\n";
    exit (1);
  }

  // sharedshelf user
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  if (!isset($task['process']['cookie_jar_path'])) {
    throw new Exception("Expecting cookie_jar_path in .ini file", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password'], $task['process']['cookie_jar_path']);

  foreach($task['configuration_files']['config'] as $config) {
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }
    if ($single_collection !== FALSE) {
      if ($project['project'] != $single_collection) {
        continue;
      }
    }

    $project_id = $project['project'];

    // find the publishing target id for this project
    $publishing_target_id = $ss->find_publishing_target_id($project_id);

    // create a log file for this collection
    $log_file_prefix = $task['process']['log_file_prefix'] . '-' . $project_id;
    $log = new SharedShelfToSolrLogger($log_file_prefix);
    $log->task("$config-$project_id-iiif");

    $log->note('project_asset_ids');
    $asset_count = $ss->project_assets_count($project_id);
    $log->note("asset_count:$asset_count");
    echo "IIIF: $config asset count: $asset_count " . $log->log_file_name() . PHP_EOL;
    $per_page = 25;
    for ($start = 0; $start < $asset_count; $start += $per_page) {
      $assets =  $ss->project_assets($project_id, $start, $per_page);
      $counter = $start;
      foreach ($assets as $asset) {
        $ss_id = $asset['id'];
        if ($single_item && $ss_id != $single_item) {
          continue;
        }
        $log->item("asset $ss_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        // determine publishing status - status_ssi
        $asset_full = $ss->asset($ss_id);
        if (isset($asset_full['publishing_status']["$publishing_target_id"]['status'])) {
          $cul_publishing_status = $asset_full['publishing_status']["$publishing_target_id"]['status'];
        }
        else {
          $cul_publishing_status  = 'Unpublished';
        }

        if (strcmp($cul_publishing_status, "Published") != 0) {
          $log->note("Publishing status: $cul_publishing_status");
        }

        try {

          $url = $ss->media_url($ss_id);

          $ext = $ss->media_file_extension($ss_id);
          if (!in_array(strtolower($ext), $supported_image_formats)) {
            throw new Exception("Unsupported image format: '$ext' ", 1);
          }

          $s3_path = "$project_id/$ss_id";

          try {
            image_to_iiif_s3($url, $ext, $s3_path, $force_replacement);
          }
          catch (\Exception $e) {
            // just log the error and continue
            $log->error("Asset $s3_path conversion to iiif failed: " . $e->getMessage());
          }

          if (FALSE) throw new Exception("shortcut to exit", 1);
        }
        catch (\Exception $e) {
          $error = 'Caught exception: ' . $e->getMessage() . " - skipping this asset\n";
          if ($log !== FALSE) {
            $log->error($error);
          }
          else {
            echo $error;
          }
        }
      }
    }

    $log->task("Done. Project $project_id.");
  }
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
