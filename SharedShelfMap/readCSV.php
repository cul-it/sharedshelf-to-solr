<?php

function readCSV($csvFile) {
    // line endings must be just LF
    $file_handle = fopen($csvFile, 'r');
    $lines_of_text = array();
    while (!feof($file_handle)) {
        if (($data = fgetcsv($file_handle, 1024)) !== FALSE) {
            $lines_of_text[] = $data;
        }
        else {
            throw new Exception("problems with CSV file $csvFile", 1);            
        }
    }
    fclose($file_handle);

    // return array with column titles as keys
    $titles = $lines_of_text[0];
    $proper = array();
    $first_count = count($lines_of_text[0]);
    for ($i = 1; $i < count($lines_of_text); $i++) {
        $row = $lines_of_text[$i];
        if (count($row) != $first_count) {
            throw new Exception("Problem with line $i of CSV file $csvFile", 1);            
        }
        $key_row = array();
        foreach($row as $index => $field) {
            $key_row[$titles[$index]] = $field;
        }
        $proper[] = $key_row;
        }
    return $proper;
}

?>