<?php
// aerial-metadata.php - collect centerpoint descriptions from aerial web site

$service = "https://aerial-ny.library.cornell.edu/export_geo/";

$datafile = "/Users/jgr25/Documents/aerial-ny/id-list.tab";
$datafile = "/Users/jgr25/Documents/aerial-ny/id-list-second-chance.tab";

// $contents = file($datafile);
// var_dump($contents);
// exit(0);

$handle = fopen($datafile, 'r');

if ($handle === FALSE) die("bad file");

$output = array();

while (($line = fgets($handle, 1024)) !== FALSE) {
  //print_r($line);
  $url = $service . trim($line);
  //print_r($url);
  $json = file_get_contents($url);
  //var_dump($json);
  $detail = json_decode($json);
  //var_dump($detail);
  $placename = $detail[0]->field_placename_value->content;
  $output[] = trim($line) . "\t" . $placename;
  echo $line;
}

fclose($handle);

file_put_contents("aerial-metadata-output-second-chance.tab", implode(PHP_EOL,$output));

