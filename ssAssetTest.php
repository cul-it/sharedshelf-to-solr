<?php
// ssAssetTest.php - test sharedshelf object
require_once('SharedShelfService.php');
require_once('SolrUpdater.php');

try {
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password']);

  if ($ss->logged_in()) {
    echo "User is logged in\n";
  }

  $assets = array(
    3853124,
    );

  foreach ($assets as $id) {
    $asset = $ss->asset($id);
    print_r($asset);
  }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
