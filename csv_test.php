<?php
// csv_test.php - tester for csv package

if (!ini_get("auto_detect_line_endings")) {
    ini_set("auto_detect_line_endings", '1');
}

require __DIR__ . '/vendor/autoload.php';

use League\Csv\Writer;

$header = ['first name', 'last name', 'email'];
$records = [
    [1, 2, 3],
    ['foo', 'bar', 'baz'],
    ['john', 'doe', 'john.doe@example.com'],
];

//load the CSV document from a string
$csv = Writer::createFromString('');

//insert the header
$csv->insertOne($header);

//insert all the records
$csv->insertAll($records);

echo $csv; //returns the CSV document as a string
