<?php
// ssTest.php - test sharedshelf object
require_once('SharedShelfService.php');

try {
  $ss = new SharedShelfService();

  if ($ss->logged_in()) {
    echo "User is logged in\n";
  }

  $projects = $ss->projects();
  print_r($projects);

  $assets = $ss->project_assets(616);
  print_r($assets);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
