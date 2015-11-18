<?php
// sharedshelf-to-solr - update all sharedshelf collections in solr

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

function debug($item, $description = '', $die = TRUE) {
  if (!empty($description)) {
    print PHP_EOL . 'DEBUG: ' . $description . PHP_EOL;
  }
  print_r($item);
  if ($die) {
    die('debugging' . PHP_EOL);
  }
}

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [--force] [-p NNN] [-s NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--force - ignore timestamps and rewrite all solr records" . PHP_EOL;
  echo "--no-write - do everything EXCEPT writing the solr records";
  echo "-p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php" . PHP_EOL;
  echo "-s - start processing at the given SharedShelf asset number NNN (NNN must be numeric) (asset numbers ascend during processing)" . PHP_EOL;
  echo "-n - process only this many (integer) assets" . PHP_EOL;
  exit (0);
}

function split_delimited_fields(&$flattened_asset, $delimited_fields = array()) {
  foreach ($delimited_fields as $key => $delimiter) {
    if (!empty($flattened_asset["$key"])) {
      $value = $flattened_asset["$key"];
      if (strpos($value, $delimiter) === FALSE) {
        $flattened_asset["$key"] = trim($value);
      }
      else {
        $items = explode($delimiter, $value);
        $items_trim = array();
        foreach ($items as $item) {
          $items_trim[] = trim($item);
        }
        $flattened_asset["$key"] = $items_trim;
      }
    }
  }
}

function get_ss_asset_list(&$ss, $project_id, $date_field) {
  $assets = $ss->project_asset_list_values($project_id, $date_field);
  $count = count($assets);
  $asset_count = $ss->project_assets_count($project_id);
  if ($count != $asset_count) {
    throw new Exception("get_ss_asset_list got the wrong number of assets: $count counted, $asset_count expected.", 1);
  }
  return $assets;
}

$log = TRUE;

$options = getopt("p:s:n:",array("help", "force", "no-write"));

if (isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
$do_not_write_to_solr = isset($options["no-write"]);
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
    $starting_asset = $options['s'];
  }
  else {
    usage();
  }
}
else {
  $starting_asset = 0;
}
if (isset($options['n'])) {
  if (is_numeric($options['n'])) {
    $max_processing_count = $options['n'];
  }
  else {
    usage();
  }
}
else {
  $max_processing_count = false; // this means process them all
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

  echo "Logging to: \ntail -50 " . $log->log_file_name() . PHP_EOL;

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
    $log->note('SolrUpdater');
    $solr_url = $project['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    // list of the ids already in solr
    $project_id = $project['project'];
    $solr_asset_id_list = $solr->get_all_ids($project_id);
    $solr_asset_ids_to_delete = array_flip($solr_asset_id_list);

    $log->note('project_asset_ids');
    $asset_count = $ss->project_assets_count($project_id);
    $log->note("asset_count:$asset_count");
    echo "$config asset count: $asset_count\n";
    $asset_list = get_ss_asset_list($ss, $project_id, 'updated_on');

    // extract list of sharedshelf field names that need special array treatment
    $delimited_fields = empty($project['delimited_field']) ? array() : $project['delimited_field'];

    // find the publishing target id for this project
    $publishing_target_id = $ss->find_publishing_target_id($project_id);

    $solr_assets = array(); // accumulate assets for solr here

    $counter = 1;
    $assets_processed = 0;
    foreach ($asset_list as $asset_id => $updated_date) {
      if ($asset_id < $starting_asset) {
        $counter++;
        continue;
      }
      if (($max_processing_count  !== false) && ($assets_processed++ >= $max_processing_count)) {
        throw new Exception("Reached the maximum count specified on the -n argument", 1);
      }
      try {
        $ss_id = $asset_id;
        $solr_id = 'ss:' . $asset_id;
        $ss_date = trim($updated_date);

        $log->item("asset $solr_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        if ($force_replacement) {
          $log->note('Job:Replace');
        }
        else {
          // is it in solr already?
          $solr_in = $solr->get_item($solr_id);
          if (empty($solr_in)) {
            $log->note('Job:AddNew');
          }
          else {
            // compare the dates
            if (empty($solr_in['updated_on_ss'])) {
              $log->note('solr missing updated_on');
              $solr_date = '';
            }
            else {
              $solr_date = trim($solr_in['updated_on_ss']);
            }
            if (strcmp($ss_date,$solr_date) == 0) {
              // dates match - skip this record
              $log->note('Job:Skip-DatesMatch');
              continue; // <--------- go on to the next asset_id !!!
            }
            else {
              $log->note('Job:Update');
            }
          }
        }

        // grab the record from sharedshelf
        $asset_full = $ss->asset($asset_id);

        // determine publishing status - status_ssi
        if (isset($asset_full['publishing_status']["$publishing_target_id"]['status'])) {
          $cul_publishing_status = $asset_full['publishing_status']["$publishing_target_id"]['status'];
        }
        else {
          $cul_publishing_status  = 'Unpublished';
        }
        $log->note(print_r($cul_publishing_status, true));

        // prepare the sharedshelf record for solr
        $asset = $ss->asset_field_values($asset_full);

        split_delimited_fields($asset, $delimited_fields);
        $solr_out = $solr->convert_ss_names_to_solr($asset);

        // check if we need images and their derivatives
        $need_images = (isset($project['has_images']) && (strcmp($project['has_images'], 'no') == 0)) ? FALSE : TRUE;
        if ($need_images) {
          $log->note('get media');
          $url = $ss->media_url($ss_id);
          $solr_out['media_URL_tesim'] = $url;
          $log->note('get derivatives');
          for ($size = 0; $size <= 4; $size++) {
            $fld = 'media_URL_size_' . $size . "_tesim";
            $solr_out["$fld"] = $ss->media_derivative_url($ss_id, $size);
          }

          $log->note('get dimensions');
          if (($dim = $ss->media_dimensions($ss_id)) !== FALSE) {
            $solr_out['img_width_tesim'] = $dim['width'];
            $solr_out['img_height_tesim'] = $dim['height'];
          }
        }

        // add in the publishing status field
        $solr_out['status_ssi'] = $cul_publishing_status;

        // be sure the id field is the solr id not the sharedshelf one
        $solr_out['id'] =  $solr_id;

        // remove any fields that will become "" in solr
        $solr_out_full = array();
        foreach ($solr_out as $key => $value) {
          if (!empty($value) || $value === FALSE) {
            $value = is_array($value) ? $value : trim($value, '"'); //hack to remove "" from Lat/Lon
            if (!empty($value)) {
              $solr_out_full["$key"] = $value;
            }
          }
        }
        if ($do_not_write_to_solr === false) {
          // add this asset to solr
          $log->note('adding to solr');
          $solr_assets = array($solr_out_full);
          $result = $solr->add($solr_assets);
        }
      }
      catch (Exception $e) {
        $error = 'Caught exception: ' . $e->getMessage() . " - skipping this asset\n";
        if ($log !== FALSE) {
          $log->error($error);
        }
        else {
          echo $error;
        }
      }
    }

    //print_r($task);
    $log->task('Done.');
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

