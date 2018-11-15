<?php

require_once "readCSV.php";

$in_filename = 'collection_metadata.csv';
$out_filename = 'myaml.yml';

$lines = readCSV($in_filename);

if (yaml_emit_file($out_filename, $lines, YAML_UTF8_ENCODING, YAML_LN_BREAK) !== TRUE) {
    throw new Exception("error on yaml_emit_file", 1);
}
