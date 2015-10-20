<?php
// ssGeoTagExtract.php - Grab Geotags from sharedshelf image

require_once('SharedShelfService.php');
require_once('SharedShelfToSolrLogger.php');

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

  $temp_image_path = '/tmp/ssGeoTagExtract';
  mkdir($temp_image_path, 0777, TRUE);
  $temp_image_file = $temp_image_path . "/temp.jpg";
  $exiftool = "exiftool -gpslatitude -gpslongitude -GPSImgDirection -c %+.6f -T $temp_image_file";
  $output_file ="./ssGeoTagExtract-output.tab";
  file_put_contents($output_file, '');

  foreach($task['configuration_files']['config'] as $config) {
    if ($config != "ss2solr.bastides.ini") {
      continue;
    }
    $log->task($config);
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }

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
      $lines = '';
      foreach ($assets as $asset) {
        $ss_id = $asset['id'];
        $solr_id = 'ss:' . $ss_id;
        $log->item("asset $solr_id");
        $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $asset_count);
        $log->note("Completed:$pct");

        $url = $ss->media_url($ss_id);
        copy($url, $temp_image_file);
        $output = shell_exec($exiftool);
        if ($output == NULL) {
          throw new Exception("Error Processing ssGeoTagExtract Request: $ss_id : $url", 1);
        }
        $parts = explode("\t", $output);
        if ($parts[0] == "-") continue; // no lat/lon data

        array_unshift($parts, $ss_id);
        array_push($parts, $asset['fd_28389_s']);
        $parts2 = array();
        foreach ($parts as $part) {
          $parts2[] = trim($part);
        }
        // var_dump($parts);
        // print_r($asset);
        // die(PHP_EOF . 'here');

        $lines .= implode("\t", $parts2) . PHP_EOL;
      }
      file_put_contents($output_file, $lines, FILE_APPEND);
    }
  }

  print_r($task);
  $log->task('Done.');

}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);
