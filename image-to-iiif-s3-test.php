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

    $ext = $ss->media_file_extension($asset_id);

    $image = array('url' => $url, 'ext' => $ext);

  }
  catch (Exception $e) {
    $error = 'Caught exception: ' . $e->getMessage() . "\n";
    echo $error;
    exit (1);
  }

  return $image;
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
$s3_path = isset($options["s3path"]) ? $options["s3path"] : usage();
$image_url = isset($options["url"]) ? $options["url"] : false;
$single_asset = isset($options['s']) ? $options['s'] : false;
if (($image_url !== false) && ($single_asset !== false)) {
  usage();
}
if ($image_url === false) {
  if (is_numeric($single_asset)) {
    // find asset image url
    $image = find_ss_image($single_asset);
    $image_url = $image['url'];
    $image_extension = $image['ext'];
  }
}
else {
  // find extension for full image url
  $image_extension = pathinfo($image_url, PATHINFO_EXTENSION);
  $image = array('url' => $image_url, 'ext' => $image_extension);
}
if ($image_url === false) {
  usage();
}

echo "image url:\n";
print_r($image);

try {
  image_to_iiif_s3($image_url, $image_extension, $s3_path, $force_replacement, $save_tmp_files);
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);

