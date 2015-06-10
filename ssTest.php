<?php
// ssTest.php - test sharedshelf object
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

  $projects = $ss->projects();
  //print_r($projects);

  /*
  48 - Campus Artifacts, Art &amp; Memorabilia
  78 - NYS Aerial Photographs
  370 - Reps Slides
  522 - Tamang
  589 - Reps Bastides
  616 - Gamelan
  746 - Ragamala Paintings
   */

  $selected_project_id = 616;

  $metadata = $ss->project_fields($selected_project_id);
  print_r($metadata);

  $assets = $ss->project_asset_ids($selected_project_id);
  //print_r($assets);
  $count = count($assets);

  $id = rand(0, $count-1);
  $asset_id = $assets["$id"];
  $asset = $ss->asset($asset_id);
  //print_r($asset);

  $values = $ss->asset_field_values($asset);
  //print_r($values);

  $url = $ss->media_url($asset_id);
  print_r($url);
  $values['Media_URL_s'] = $url;

  echo "\n\n";

  echo $ss->project_fields_ini($selected_project_id);

  echo "\n\n";
  $solr = new SolrUpdater('http://jrc88.solr.library.cornell.edu/solr', 'ss2solr.gamelan.ini');
  $solr->add_custom_fields($values);
  $json = $solr->format_update_asset_field_values($values);
  echo $json;

  echo "\n\n";
  $json = $solr->format_add_asset_field_values($values);
  echo $json;

  //$asset_list = $ss->project_assets($selected_project_id, 0, 5);
  //print_r($asset_list);

  // $modified = $ss->assets_modified_since_request($selected_project_id);
  // print_r($modified);


  echo "\n\n";
  $asset_ids = $ss->project_asset_ids($selected_project_id, '2014-10-10T14:39:58+00:00');
  print_r($asset_ids);
  $t = count($asset_ids);
  echo "Counted $t\n";

  echo "\n\n";
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
