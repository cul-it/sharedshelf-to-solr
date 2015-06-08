<?php
// ssTest.php - test sharedshelf object
require_once('SharedShelfService.php');

try {
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password']);

  if ($ss->logged_in()) {
    echo "User is logged in\n";
  }

  $projects = $ss->projects();
  //print_r($projects);

  $selected_project_id = 616;

  $metadata = $ss->project_fields($selected_project_id);
  print_r($metadata);

  $assets = $ss->project_assets($selected_project_id);
  //print_r($assets);
  $count = count($assets);

  $id = rand(0, $count-1);
  $asset = $ss->asset($assets["$id"]);
  print_r($asset);

  $values = $ss->asset_field_values($asset);
  print_r($values);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
