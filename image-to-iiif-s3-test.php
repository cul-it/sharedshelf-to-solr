<?php
// image-to-iiif-s3-test.php - command line test for functions in image-to-iiif-s3.php

require_once('SharedShelfService.php');
require_once('image-to-iiif-s3.php');

function find_ss_image($asset_id) {
  try {

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

    $url = $ss->media_url($asset_id);

  }
  catch (Exception $e) {
    $error = 'Caught exception: ' . $e->getMessage() . "\n";
    echo $error;
    exit (1);
  }

  return $url;
}

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--force] [--help] [--save] --url http://... --s3path xx/yy/zz" . PHP_EOL;
  echo "--force - ignore existing image and rewrite all iiif tiles (otherwise assumes if it's there it's good)" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--save - do not delete temp files afterwards" . PHP_EOL;
  echo "--url - url of image file (do not use -s)" . PHP_EOL;
  echo "--s3path - where to put image tiles on S3 (unique directory name) REQUIRED" . PHP_EOL;
  echo "-s NNN - process only asset NNN (do not use --url)" . PHP_EOL;
  exit (0);
}


$options = getopt("s:",array("help", "url:", "s3path:", "force", "save"));

if (isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
$save_tmp_files = isset($options["save"]);
$image_url = isset($options["url"]) ? $options["url"] : false;
$s3_path = isset($options["s3path"]) ? $options["s3path"] : usage();
$single_asset = isset($options['s']) ? $options['s'] : false;
if ($single_asset === false) {
  if ($image_url === false) {
    usage();
  }
}
else {
  if (is_numeric($single_asset)) {

    // find asset in sharedshelf
    $image_url = find_ss_image($single_asset);
  }
  else {
    usage();
  }
}

try {
  image_to_iiif_s3($image_url, $s3_path, $force_replacement, $save_tmp_files);
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);

