<?php
// sharedshelf-to-solr - update all sharedshelf collections in solr

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

define("FORCE_REPLACEMENT", FALSE);

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

  foreach($task['configuration_files']['config'] as $config) {
    $log->task($config);
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }
    //print_r($project);
    $log->note('SolrUpdater');
    $solr_url = $project['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    $log->note('project_asset_ids');
    $project_id = $project['project'];
    $asset_count = $ss->project_assets_count($project_id);
    $log->note("asset_count:$asset_count");
    echo "$config asset count: $asset_count\n";
    $per_page = 25;
    for ($start = 0; $start < $asset_count; $start += $per_page) {
      $assets =  $ss->project_assets($project_id, $start, $per_page);
      $solr_assets = array();
      $counter = $start;
      foreach ($assets as $asset) {
        $ss_id = $asset['id'];
        $solr_id = 'ss:' . $ss_id;
        $log->item("asset $solr_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        // is this asset in solr already?
        $solr_in = $solr->get_item($solr_id);
        if (empty($solr_in)) {
          // just add the asset to solr
          $log->note('Job:AddNew');
          $flattened_asset = $ss->asset_field_values($asset);
          $solr_out = $solr->convert_ss_names_to_solr($flattened_asset);
        }
        else {
          // compare the dates
          if (empty($asset['updated_on'])) {
            throw new Exception("Missing updated_on field on sharedshelf asset $ss_id ", 1);
          }
          $ss_date =  trim($asset['updated_on']);
          if (empty($solr_in['updated_on_s'])) {
            $log->note('solr missing updated_on');
            $solr_date = FALSE;
          }
          else {
            $solr_date = trim($solr_in['updated_on_s']);
          }
          if (FORCE_REPLACEMENT) {
            $log->note('Job:Replace');
          }
          else if ($ss_date == $solr_date) {
            // dates match - skip this record
            $log->note('Job:Skip-DatesMatch');
            continue;
          }
          else {
            $log->note('Job:Update');
          }
          $flattened_asset = $ss->asset_field_values($asset);
          $solr_new = $solr->convert_ss_names_to_solr($flattened_asset);
          $solr_out = array_replace($solr_in, $solr_new);
        }
        $url = $ss->media_url($ss_id);
        $solr_out['Media_URL_s'] = $url;
        for ($size = 0; $size <= 4; $size++) {
          $fld = 'Media_URL_size_' . $size . "_s";
          $solr_out["$fld"] = $ss->media_derivative_url($ss_id, $size);
        }
        $solr_out['id'] =  $solr_id;
        $solr_assets[] = $solr_out;
      }
      if (!empty($solr_assets)) {
        $result = $solr->add($solr_assets);
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

