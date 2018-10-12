<?php
// genList.php - list all fields

ini_set('memory_limit', '512M');

require_once '../SharedShelfService.php';
require_once 'SharedShelfMetadataApplicationProfile.php';


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

    $fields = $map->listFields();
    echo implode("\n", $fields) . "\n";
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
