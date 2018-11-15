<?php

require_once "readCSV.php";

$in_filename = 'collection_metadata.csv';
$out_filename = 'myaml.yml';

$lines = readCSV($in_filename);
$sorted = array();

foreach ($lines as $line) {
    $nonick = array();
    if (!empty($line['nickname'])) {
        $ini = array();
        foreach ($line as $key => $value) {
            if (!empty($value)) {
                $ini[] = $key . ' = "' . trim($value) . '"';
            }
        }
        $section = $line['nickname'];
        $sorted["$section"] = $ini;
    }
    else {
        $nonick[] = $line['collection_id'];
    }
}
ksort($sorted);
foreach ($sorted as $key => $value) {
    echo "[$key]\n";
    echo implode("\n", $value);
    echo "\n\n";
}
