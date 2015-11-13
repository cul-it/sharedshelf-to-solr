<?php
// listPublicationTargets.php - list the id of the publication targets for each project
// php listPublicationTargets.php
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

  echo "publication_target_id is the last number on each line....\n";

  $projects = $ss->projects();
  foreach ($projects['items'] as $project) {
    //echo $project['id'] . ' - ' . $project['name'] . PHP_EOL;
    $meta = $ss->project_metadata($project['id']);
    if (is_array($meta['targets'])) {
      foreach ($meta['targets'] as $target) {
        $info = array($project['id'], $project['name'], $target['target_name'], $target['target_id'], $target['id']);
        echo implode(' - ', $info) . PHP_EOL;

        $id = $ss->find_publishing_target_id($project['id'], $target['target_name']);
        if ($id != $target['id']) {
          throw new Exception("find_publishing_target returned wrong publishing target!!", 1);

        }
      }
    }
  }

/*
48 - Campus Artifacts, Art &amp; Memorabilia
78 - NYS Aerial Photographs
370 - Reps Slides
522 - Tamang
589 - Reps Bastides
616 - Gamelan
659 - PJ Mode Map Collection
687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia
746 - Ragamala Paintings
 */
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

