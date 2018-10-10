<?php
require "readCSV.php";

try {
    $csv = "collection_metadata.csv";
    $lines = readCSV($csv);
    print_r($lines);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
