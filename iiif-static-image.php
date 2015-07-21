<?php
// iiif-static-image.php - generate static iiif images

function iiif_static_image($url, $collection_id, $image_id) {
  $path = "iiif-static-images/$collection_id/$image_id";
  $command = "./iiif-static-image.sh $url $path";
  $output = '';
  $return_var = 0;
  $lastline = exec($command, $output, $return_var);
  if ($return_var != 0) {
    $output[] = 'Command failed: ' .  $command;
    $out = implode("PHP_EOL", $output);
    throw new Exception("Error Processing iiif: $out", 1);
  }
}

$url = 'http://stor.artstor.org/stor/55533d5d-a593-44b9-95a8-55dcd2121eb8.jpg';
$collection_id = 'test';
$image_id = '201711';

iiif_static_image($url, $collection_id, $image_id);
