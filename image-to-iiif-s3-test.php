<?php
// image-to-iiif-s3-test.php - command line test for functions in image-to-iiif-s3.php

require_once('image-to-iiif-s3.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--force] [--help] [--save] --url http://... --s3path xx/yy/zz" . PHP_EOL;
  echo "--force - ignore existing image and rewrite all iiif tiles (otherwise assumes if it's there it's good)" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--save - do not delete temp files afterwards" . PHP_EOL;
  echo "--url - url of image file" . PHP_EOL;
  echo "--s3path - where to put image tiles on S3 (unique directory name)" . PHP_EOL;
  exit (0);
}


$options = getopt("",array("help", "url:", "s3path:", "force", "save"));

if (isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
$save_tmp_files = isset($options["save"]);
$image_url = isset($options["url"]) ? $options["url"] : usage();
$s3_path = isset($options["s3path"]) ? $options["s3path"] : usage();

try {
  image_to_iiif_s3($image_url, $s3_path, $force_replacement, $save_tmp_files);
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);

