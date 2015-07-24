<?php
// image-to-iiif-s3.php - convert an image to static iiif tiles and move tiles to Amazon s3

define('OUTPUT', TRUE);

function image_to_iiif_s3_mkdir($path) {
  // make a directory at the given path
  if (!is_dir($path)) {
    if (mkdir($path, 0775, TRUE) === FALSE) {
      throw new Exception("Can't create directory : $path", 1);
    }
  }
}

function image_to_iiif_s3($image_url, $s3_path, $force_replacement = FALSE, $save_tmp_files = FALSE) {

  $s3_bucket = 's3://sharedshelftosolr.library.cornell.edu/public';
  $s3_url_prefix = 'http://s3.amazonaws.com/sharedshelftosolr.library.cornell.edu/public';

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
      return;
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
  $s3path = "$s3_url_prefix/$s3_path";
  $osd = "/cul/share/openseadragon/openseadragon-1.2.1"; // path to
  //$command = "python $script -d $local_iiif_dir --tilesize 800 $local_image";
  //$command = "python $script -d $local_iiif_dir $local_image";
  $command = "python $script --api-version 2.0 -d $local_iiif_dir -p $s3path --osd $osd $local_image";
  $output = '';
  $return_var = 0;
  $lastline = exec($command, $output, $return_var);
  if ($return_var != 0) {
    $output[] = 'Command failed: ' .  $command;
    $out = implode("PHP_EOL", $output);
    throw new Exception("Error Processing iiif: $out", 1);
  }

  //hack: rewrite the @id from 'iiif' to the s3 path to info.json
  // $s3path = "$s3_url_prefix/$s3_path";
  // $info_file = "$local_iiif_dir/info.json";
  // if (($json = file_get_contents($info_file)) === FALSE) {
  //   throw new Exception("can't get file contents: $info_file", 1);
  //   }
  // $json2 = preg_replace('/"@id": "([^"]+)"/', '"@id": "' . $s3path . '"', $json);
  // if (file_put_contents($info_file, $json2) === FALSE) {
  //   throw new Exception("Can not write file: $info_file", 1);
  //   }

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

  // delete temp files
  if ($save_tmp_files) {
    if (OUTPUT) echo "Saving $temp_dir.\n";
  }
  else {
    if (OUTPUT) echo "Deleting $temp_dir.\n";
    system('/bin/rm -rf ' . $temp_dir);
  }

  if (OUTPUT) {
    $s3path = "$s3_url_prefix/$s3_path/info.json";
    echo "Done: $s3path\n";
  }
}
