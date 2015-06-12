<?php
// listFields.php - list the fields in a project
// php listFields.php ss2solr.tamang.ini
// use configuration file name as argument
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
  foreach ($projects['items'] as $project) {
    echo $project['id'] . ' - ' . $project['name'] . PHP_EOL;
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
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

