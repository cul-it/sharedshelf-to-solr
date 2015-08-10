<?php
// sharedshelf-status.php - determine if sharedshelf items have been converted to solr and iiif

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');
require_once('image-to-iiif-s3.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [-p NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "-p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php" . PHP_EOL;
  exit (0);
}

$log = FALSE;

$options = getopt("p:",array("help"));

if (isset($options['help'])) {
  usage();
}
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
  $task = parse_ini_file("sharedshelf-status.ini", TRUE);
  if ($task === FALSE) {
    echo "Need sharedshelf-status.ini\n";
    exit (1);
  }

  // open log
  if (empty($task['process']['log_file_prefix'])) {
    echo "Need log_file_prefix\n";
    exit (1);
  }

  $log = new SharedShelfToSolrLogger($task['process']['log_file_prefix']);

    // sharedshelf user
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  if (!isset($task['process']['cookie_jar_path'])) {
    throw new Exception("Expecting cookie_jar_path in .ini file", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password'], $task['process']['cookie_jar_path']);

  $progress = 0;

  foreach($task['configuration_files']['config'] as $config) {
    $log->task($config);
    $status = array('project' => 0, 'assets' => 0, 'solr' => 0, 'iiif' => 0, 'update_solr' => 0);
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
    $solr_url = $project['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    $project_id = $project['project'];
    $status['project'] = $project_id . " $config";
    $asset_count = $ss->project_assets_count($project_id);
    echo "$config asset count: $asset_count\n";
    $per_page = 25;
    for ($start = 0; $start < $asset_count; $start += $per_page) {
      $assets =  $ss->project_assets($project_id, $start, $per_page);
      $solr_assets = array();
      $counter = $start;
      foreach ($assets as $asset) {
        if ($progress++ % 100 == 0) {
          echo '.';
        }
        $status['assets']++;
        $ss_id = $asset['id'];
        $solr_id = 'ss:' . $ss_id;
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->item("asset $ss_id");
        $log->note("Completed:$pct");

        try {
          // is this asset in solr already?
          $solr_in = $solr->get_item($solr_id);
          if (!empty($solr_in)) {
            $status['solr']++;
            $log->note('inSolr');

            // compare the dates
            if (empty($asset['updated_on'])) {
              throw new Exception("Missing updated_on field on sharedshelf asset $ss_id ", 1);
            }
            $ss_date =  trim($asset['updated_on']);
            if (empty($solr_in['updated_on_ss'])) {
              $solr_date = '';
            }
            else {
              $solr_date = trim($solr_in['updated_on_ss']);
            }
            if (strcmp($ss_date,$solr_date) != 0) {
               $status['update_solr']++; // last update dates do not match
               $log->note('SolrNeedsUpdate');
            }
          }

          // is there a iiif version of the asset?
          $s3_path = "$project_id/$ss_id/image/info.json";
          $s3_bucket = 's3://sharedshelftosolr.library.cornell.edu/public';
          $s3_url_prefix = 'http://s3.amazonaws.com/sharedshelftosolr.library.cornell.edu/public';

          $command = "s3cmd ls $s3_bucket/$s3_path";
          $output = '';
          $return_var = 0;
          $lastline = exec($command, $output, $return_var);
          if ($return_var != 0) {
            $output[] = 'Command failed: ' .  $command;
            $out = implode("PHP_EOL", $output);
            throw new Exception("Error Processing checking for iiif on s3: $out", 1);
          }
          if (strpos($lastline, $s3_path) !== FALSE) {
            $status['iiif']++;
            $log->note('HasIIIF');
          }
          else {
            $log->note('NeedsIIIF');
          }
        }
        catch (\Exception $e) {
          $error = 'Caught exception: ' . $e->getMessage() . " - skipping this asset\n";
          echo $error;
          if ($log !== FALSE) {
            $log->error($error);
          }
        }
      }
    }
    echo "\nProject: " . $status['project'] . "\n";
    echo "Assets: " . $status['assets'] . "\n";
    echo "In Solr: " . $status['solr'] . "\n";
    echo "Solr needs update: " . $status['update_solr'] . "\n";
    echo "In iiif on s3: " . $status['iiif'] . "\n";
  }

  print_r($task);
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);
