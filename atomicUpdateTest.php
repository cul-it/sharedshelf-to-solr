<?php
// atomicUpdateTest.php - testing the Atomic Update feature of solr
// https://wiki.apache.org/solr/UpdateJSON#Atomic_Updates

$blop = <<<EOF
[
 {
  "id"        : "TestDoc1",
  "title"     : {"set":"test1"},
  "revision"  : {"inc":3},
  "publisher" : {"add":"TestPublisher"}
 },
 {
  "id"        : "TestDoc2",
  "publisher" : {"add":"TestPublisher"}
 }
]
EOF;

$blip = json_decode($blop);

print_r($blip);

$blup = json_encode($blip);

echo $blup;

$cast_blup = (object) $blup;
$blup2 = json_encode($cast_blup);
echo "\n";
echo $blup2;
echo "\n";
echo (strcmp($blup, $blup2) == 0) ? "same\n" : "differtent\n";
