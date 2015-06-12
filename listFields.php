<?php
// listFields.php - list the fields in a project
// php listFields.php ss2solr.tamang.ini
// use configuration file name as argument
require_once('SharedShelfService.php');

try {
  if (!isset($argv[1])) {
    $usage = 'Usage: php ' . $argv[0] . ' 123 // where 123 is the sharedshelf project ID' . PHP_EOL;
    throw new Exception($usage, 1);
  }
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password']);

  if ($ss->logged_in()) {
    echo "User is logged in\n";
  }

  /*
  48 - Campus Artifacts, Art &amp; Memorabilia
  78 - NYS Aerial Photographs
  370 - Reps Slides
  522 - Tamang
  589 - Reps Bastides
  616 - Gamelan
  746 - Ragamala Paintings
   */

  $selected_project_id = $argv[1];

  $metadata = $ss->project_fields_ini($selected_project_id);
  print_r($metadata);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

