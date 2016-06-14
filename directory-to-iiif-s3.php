<?php
// directory-to-iiif-s3.php - convert all the image files in a directory to iiif
require_once('image-to-iiif-s3.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [--force] -f /path/to/files -c collection " . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "--force - ignore existing and rewrite all iiif images" . PHP_EOL;
  echo "-f - path to local directory of image files" . PHP_EOL;
  echo "-c - name of the collection (used in the s3 path)" . PHP_EOL;
  exit (0);
}

$log = FALSE;

$options = getopt("f:c:",array("help", "force"));

if (isset($options['help'])) {
  usage();
}

$force_replacement = isset($options["force"]);
if (isset($options['f'])) {
  $directory = $options['f'];
}
else {
  usage();
}
if (isset($options['c'])) {
  $collection = $options['c'];
}
else {
  usage();
}

try {

  if (!is_dir($directory)) {
    throw new Exception("Not a directory: $directory", 1);
  }

  if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
        if ('.' === $file) continue;
        if ('..' === $file) continue;

        $image_url = "$directory/$file";
        $path_parts = pathinfo($image_url);
        $extension = $path_parts['extension'];
        $filename = $path_parts['filename'];
        if (empty(trim($filename)) || empty(trim($extension))) {
          continue;
        }
        $s3_path = "$collection/$filename";
        print "$image_url -> $s3_path\n";
        image_to_iiif_s3($image_url, $extension, $s3_path, $force_replacement);

    }
    closedir($handle);
  }
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);

?>
