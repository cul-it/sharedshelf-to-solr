<?php
// genini.php - generate .ini files for sharedshelf collections
ini_set('memory_limit', '512M');

require_once '../SharedShelfService.php';
require_once 'SharedShelfMetadataApplicationProfile.php';
require_once "readCSV.php";


try {
    $user = parse_ini_file('../ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
  
    $ss = new SharedShelfService($user['email'], $user['password']);
  
    if ($ss->logged_in()) {
      echo "User is logged in\n";
    }
    
    $map = new SharedShelfMetadataApplicationProfile($ss);

    // loop over projects in csv file collection_metadata.ini
    $csv = "collection_metadata.csv";
    $lines = readCSV($csv);
    foreach ($lines as $line) {
        if ($line['active'] == 'no') {
            continue;
        }
        $format = "Project: %d - nicknamed: %s - titled: %s\n";
        echo sprintf($format, $line['collection_id'], $line['nickname'], $line['collection_name']);
        $format = "ini/ss2solr.%s.ini";
        $filename = sprintf($format, $line['nickname']);

        $map->set_project($line['collection_id']);

        $ini = $map->get_map_as_ini();

        file_put_contents($filename, $ini);
        echo " Written to $filename\n";
    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
