<?php

function readCSV($csvFile) {
    // line endings must be just LF
    $file_handle = fopen($csvFile, 'r');
    $lines_of_text = array();
    while (!feof($file_handle)) {
        $lines_of_text[] = fgetcsv($file_handle, 1024);
    }
    fclose($file_handle);

    // return array with column titles as keys
    $titles = $lines_of_text[0];
    $proper = array();
    for ($i = 1; $i < count($lines_of_text); $i++) {
        $row = $lines_of_text[$i];
        $key_row = array();
        foreach($row as $index => $field) {
            $key_row[$titles[$index]] = $field;
        }
        $proper[] = $key_row;
        }
    return $proper;
}

?>