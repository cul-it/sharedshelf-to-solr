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
  print_r($projects);

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

  $selected_project_id = 687;

  $metadata = $ss->project_fields($selected_project_id);
  print_r($metadata);

  $assets = $ss->project_asset_ids($selected_project_id);
  //print_r($assets);
  $count = count($assets);
  $asset_count = $ss->project_assets_count($selected_project_id);
  if ($count != $asset_count) {
    print "ERROR: Asset count mismatch: $count vs. $asset_count\n";
  }

  $missing = 3857010;
  if (in_array($missing, $assets)) {
    print "Found $missing in the returned assset list.\n";
  }
  else {
    print "Asset $missing is missing from the asset list.\n";
  }

  // try getting all assets one by one
  $assets_piecemeal = array();
  for ($offset = 0; $offset < $asset_count; $offset++) {
    $asset_list =  $ss->project_assets($selected_project_id, $offset, 1);
    $asset = array_shift($asset_list);
    $assets_piecemeal[] = $asset['id'];
  }
  if (in_array($missing, $assets_piecemeal)) {
    print "Found $missing in the returned piecemeal assset list.\n";
  }
  else {
    print "Asset $missing is missing from the piecemeal asset list.\n";
  }

  $piecemeal_count = count($assets_piecemeal);
  print "collection has $asset_count, piecemeal requests found $piecemeal_count.\n";

  $diff = array_diff($assets, $assets_piecemeal);
  print "Difference:\n";
  print_r($diff);

  // $id = rand(0, $count-1);
  // $asset_id = $assets["$id"];
  $asset_id = $missing;
  $asset = $ss->asset($asset_id);
  //print_r($asset);

  $values = $ss->asset_field_values($asset);
  print_r($values);

  $url = $ss->media_url($asset_id);
  print_r($url);
  $values['Media_URL_s'] = $url;

  echo "\n\n";

  // echo $ss->project_fields_ini($selected_project_id);

  // echo "\n\n";
  // $solr = new SolrUpdater('http://jrc88.solr.library.cornell.edu/solr', 'ss2solr.gamelan.ini');
  // $solr->add_custom_fields($values);
  // $json = $solr->format_update_asset_field_values($values);
  // echo $json;

  // echo "\n\n";
  // $json = $solr->format_add_asset_field_values($values);
  // echo $json;

  //$asset_list = $ss->project_assets($selected_project_id, 0, 5);
  //print_r($asset_list);

  // $modified = $ss->assets_modified_since_request($selected_project_id);
  // print_r($modified);


  // echo "\n\n";
  // $asset_ids = $ss->project_asset_ids($selected_project_id, '2014-10-10T14:39:58+00:00');
  // print_r($asset_ids);
  // $t = count($asset_ids);
  // echo "Counted $t\n";

  echo "\n\n";
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
