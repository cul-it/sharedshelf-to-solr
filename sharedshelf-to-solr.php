<?php
// sharedshelf-to-solr - update all sharedshelf collections in solr

ini_set('memory_limit', '512M');

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
  echo "Usage: php " . $argv[0] . " [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--force - ignore timestamps and rewrite all solr records" . PHP_EOL;
  echo "--no-write - do everything EXCEPT writing the solr records" . PHP_EOL;
  echo "--use-dev-solr - override the solr core specified in .ini file using http://jrc88.solr.library.cornell.edu/solr/digitalcollections_dev" . PHP_EOL;
  echo "--skip - do not process this collection (only when -p is specified)" . PHP_EOL;
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
        $trimmed = trim($value);
        if (!empty($trimmed)) {
          $flattened_asset["$key"] = $trimmed;
        }
      }
      else {
        $items = explode($delimiter, $value);
        $items_trim = array();
        foreach ($items as $item) {
          $trimmed = trim($item);
          if (!empty($trimmed)) {
            $items_trim[] = $trimmed;
          }
        }
        if (!empty($items_trim)) {
          $flattened_asset["$key"] = $items_trim;
        }
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

$log = FALSE;

$options = getopt("p:s:n:",array("help", "force", "no-write", "use-dev-solr", "skip"));

if ($options === false || isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
$skip_this_collection = isset($options["skip"]);
$do_not_write_to_solr = isset($options["no-write"]);
$solr_collection_override = isset($options["use-dev-solr"]) ?
  "http://jrc88.solr.library.cornell.edu/solr/digitalcollections_dev" : false;
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

$option_text = $single_collection ? "project $single_collection " : 'all ';
$option_text .= $force_replacement ? 'force ' : '';
$option_text .= $do_not_write_to_solr ? 'no-write ' : '';
$option_text .= $solr_collection_override ? 'use-dev-solr ' : '';

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
        // skip any other collection if one is listed on the command line
        continue;
      }
      if ($skip_this_collection) {
        // skip any collection with the --skip flag
        continue;
      }
    }

    $project_id = $project['project'];

    // create a log file for this collection
    $log_file_prefix = $task['process']['log_file_prefix'] . '-' . $project_id;
    $log = new SharedShelfToSolrLogger($log_file_prefix);
    $log->task("$config-$project_id");

    $log->note('SolrUpdater');
    $solr_url = ($solr_collection_override !== false) ? $solr_collection_override : $project['solr'];
    $log->note($solr_url);
    $solr = new SolrUpdater($solr_url, $config);

    // list of the ids already in solr
    $solr_asset_id_list = $solr->get_all_ids($project_id);
    $solr_asset_ids_to_delete = array_flip($solr_asset_id_list);

    $log->note('project_asset_ids');
    $asset_count = $ss->project_assets_count($project_id);
    $log->note("asset_count:$asset_count");
    echo "Processing: $option_text $config asset count: $asset_count " . $log->log_file_name() . PHP_EOL;
    $asset_list = get_ss_asset_list($ss, $project_id, 'updated_on');

    // extract list of sharedshelf field names that need special array treatment
    $delimited_fields = empty($project['delimited_field']) ? array() : $project['delimited_field'];

    // find the publishing target id for this project
    if (empty($project['publishing_target_id'])) {
      $publishing_target_id = $ss->find_publishing_target_id($project_id);
    }
    else {
      // this comes from the publishing_target_id of the publishing_status field
      $publishing_target_id = $project['publishing_target_id'];
    }

    $solr_assets = array(); // accumulate assets for solr here

    $counter = 1;
    $assets_processed = 0;
    foreach ($asset_list as $asset_id => $updated_date) {
      $ss_id = $asset_id;
      $solr_id = 'ss:' . $asset_id;

      // eliminate asset ids that still exist in sharedshelf from the delete list
      unset($solr_asset_ids_to_delete["$solr_id"]);

      if ($asset_id < $starting_asset) {
        $counter++;
        continue;
      }
      if (($max_processing_count  !== false) && ($assets_processed++ >= $max_processing_count)) {
        throw new Exception("Reached the maximum count specified on the -n argument", 1);
      }
      try {
        $ss_date = trim($updated_date);

        $log->item("asset $solr_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        /**
         * Find any existing solr asset so we can preserve
         * data others may have stored there
         */
        $solr_in = $solr->get_item($solr_id);

        if ($force_replacement) {
          $log->note('Job:Replace');
        }
        else {
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
          $jsondetails = $ss->media_iiif_info($ss_id);
          if (!empty($jsondetails)) {
            $solr_out ['content_metadata_image_iiif_info_ssm'] = $jsondetails['info_url'];;
            $solr_out ['content_metadata_first_image_width_ssm'] = $jsondetails['width'];;
            $solr_out ['content_metadata_first_image_height_ssm'] = $jsondetails['height'];;
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
            if (!is_array($value)) {
             $value = trim($value);
              // just a pair of double quotes?
              if (strcmp($value, '""') == 0) {
                $value = '';
              }
              else {
                // attempt to remove double quotes from decimal numbers (used to prevent rounding in SS)
                $value = preg_replace('/^\"([0-9]*\.[0-9]*)\"$/', '\1', $value);
              }
            }
            if (!empty($value)) {
              $solr_out_full["$key"] = $value;
            }
          }
        }
        if ($do_not_write_to_solr === false) {
          // add this asset to solr
          $log->note('adding to solr');
          // merge sharedshelf stuff into what was already in the solr document
          if (empty($solr_in)) {
            $merged = $solr_out_full;
          }
          else {
            $merged = array_merge($solr_in,$solr_out_full);
          }
          $solr_assets = array($merged);
          $result = $solr->add($solr_assets, FALSE);
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

    // delete assets from solr that are no longer in sharedshelf
    if ($do_not_write_to_solr === false) {
      if (!empty($solr_asset_ids_to_delete)) {
        $ids = array_flip($solr_asset_ids_to_delete);
        $log->note("Deleting solr ids: " . implode(', ', $ids));
        $solr->delete_items($ids);
      }
      else {
        $log->note("No solr asssets to delete for project $project_id.");
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

