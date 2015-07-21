<?php
// iiif-static-image.php - generate static iiif images

function iiif_static_mkdir($path) {
  // make a directory at the given path
  if (!is_dir($path)) {
    if (mkdir($path, 0775, TRUE) === FALSE) {
      throw new Exception("Can't create directory : $path", 1);
    }
  }
}

function iiif_static_image($url, $collection_id, $image_id) {

  // create local directory
  $dir = realpath(dirname(__FILE__));
  $path = "$dir/iiif-static-images/$collection_id/$image_id";
  iiif_static_mkdir($path);

  // create a local copy of the image file
  $temp_path = "/tmp/iiif_static_image/$collection_id";
  iiif_static_mkdir($temp_path);
  $ext = pathinfo($url, PATHINFO_EXTENSION);
  $ext = empty($ext) ? 'jpg' : $ext;
  $local_copy = "$local_path/$image_id.$ext";
  if (!copy($url,$local_copy)) {
    throw new Exception("Can't copy $url to local", 1);
  }

  $script = "/cul/share/iiif/iiif/iiif_static.py";
  $command = "python $script $local_copy $path";
  $output = '';
  $return_var = 0;
  $lastline = exec($command, $output, $return_var);
  if ($return_var != 0) {
    $output[] = 'Command failed: ' .  $command;
    $out = implode("PHP_EOL", $output);
    throw new Exception("Error Processing iiif: $out", 1);
  }
}

// code for testing the function
$url = 'http://stor.artstor.org/stor/55533d5d-a593-44b9-95a8-55dcd2121eb8.jpg';
$collection_id = 'test';
$image_id = '201711';

iiif_static_image($url, $collection_id, $image_id);
