<?php
// csv_from_ini.php - convert .ini file to csv

if (!ini_get("auto_detect_line_endings")) {
    ini_set("auto_detect_line_endings", '1');
}

require __DIR__ . '/vendor/autoload.php';

use League\Csv\Writer;

$sample = "ss2solr.adler.ini";

$ini = parse_ini_file($sample);

print_r($ini);

$header = ['solr_field', 'source', 'default', 'delimiter', 'solr_single', 'solr_copy_source', 'solr_latitude_decimal', 'solr_longitude_decimal'];

$rows[] = array('solr_field' => 'solr', 'default' => $ini['solr']);
$rows[] = array('solr_field' => 'project', 'default' => $ini['project']);

foreach ($ini['fields'] as $key => $value) {
  $row['solr_field'] = $value;
  $row['source'] = $key;

  // defaults keyed by solr field
  if (isset($ini['set_solr_field']["$value"])) {
    $row['default'] = $ini['set_solr_field']["$value"];
  }

  // delimiters keyed by source field
  if (isset($ini['delimited_field']["$key"])) {
    $row['delimiter'] = $ini['delimited_field']["$key"];
  }

  $rows[] = $row;
}

foreach ($ini['set_single_value'] as $key => $value) {
  $row['solr_field'] = $key;
  $row['solr_single'] = $value;

  $rows[] = $row;
}

foreach ($rows as $row) {
  $record = array();
  foreach ($header as $col) {
    $record[] = isset($row["$col"]) ? $row["$col"] : '';
  }
  $records[] = $record;
}


//load the CSV document from a string
$csv = Writer::createFromString('');

//insert the header
$csv->insertOne($header);

//insert all the records
$csv->insertAll($records);

echo $csv; //returns the CSV document as a string
