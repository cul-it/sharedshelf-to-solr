<?php
// image-to-iiif-s3.php - convert an image to static iiif tiles and move tiles to Amazon s3

define('OUTPUT', TRUE);

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--force] [--help] --url http://... --s3path xx/yy/zz" . PHP_EOL;
  echo "--force - ignore existing image and rewrite all iiif tiles (otherwise assumes if it's there it's good)" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--url - url of image file" . PHP_EOL;
  echo "--s3path - where to put image tiles on S3 (unique directory name)" . PHP_EOL;
  exit (0);
}


function image_to_iiif_s3_mkdir($path) {
  // make a directory at the given path
  if (!is_dir($path)) {
    if (mkdir($path, 0775, TRUE) === FALSE) {
      throw new Exception("Can't create directory : $path", 1);
    }
  }
}

$options = getopt("",array("help", "url:", "s3path:", "force"));

if (isset($options['help'])) {
  usage();
}
$force_replacement = isset($options["force"]);
$image_url = isset($options["url"]) ? $options["url"] : usage();
$s3_path = isset($options["s3path"]) ? $options["s3path"] : usage();

$s3_bucket = 's3://sharedshelftosolr.library.cornell.edu/public';

if (OUTPUT) echo "Checking pre-existing.\n";

// check if it already exists on S3
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
  // assume this image has already been processed
  if (!$force_replacement) {
    // user did not want to replace any existing image
    exit(0);
  }
}
if (OUTPUT) {
  echo "$command\n";
  echo implode("\n",$output) . "\n";
}

// find extension of filename in image url
$ext = pathinfo($image_url, PATHINFO_EXTENSION);
if (empty($ext)) {
  throw new Exception("Need image type extension on url: $image_url", 1);
}

if (OUTPUT) echo "Create directories.\n";

// create temporary directories
$temp_dir = "/tmp/image-to-iiif-s3/$s3_path";
image_to_iiif_s3_mkdir($temp_dir);
$local_image = "$temp_dir/image.$ext";
$local_iiif_dir = "$temp_dir/iiif";
image_to_iiif_s3_mkdir($local_iiif_dir);

if (OUTPUT) echo "$temp_dir\n";

if (OUTPUT) echo "Local copy of image.\n";

// make a local copy of the image
if (!copy($image_url,$local_image)) {
  throw new Exception("Can't copy $image_url to local", 1);
}

if (OUTPUT) echo "Making iiif tiles.\n";

// generate the static iiif tiles
$script = "/cul/share/iiif/iiif/iiif_static.py";
//$command = "python $script -d $local_iiif_dir --tilesize 800 $local_image";
$command = "python $script -d $local_iiif_dir --tilesize 1024 $local_image";
$output = '';
$return_var = 0;
$lastline = exec($command, $output, $return_var);
if ($return_var != 0) {
  $output[] = 'Command failed: ' .  $command;
  $out = implode("PHP_EOL", $output);
  throw new Exception("Error Processing iiif: $out", 1);
}

if (OUTPUT) echo "Moving tiles to S3.\n";

// rsync tiles to s3
$command = "s3cmd sync $local_iiif_dir/ $s3_bucket/$s3_path/";
$output = '';
$return_var = 0;
$lastline = exec($command, $output, $return_var);
if ($return_var != 0) {
  $output[] = 'Command failed: ' .  $command;
  $out = implode("PHP_EOL", $output);
  throw new Exception("Error Processing checking for iiif on s3: $out", 1);
}

if (OUTPUT) echo "Done.\n";

