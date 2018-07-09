<?php
ini_set('memory_limit', '512M');

require_once('../SharedShelfService.php');
require_once('SharedShelfMetadataApplicationProfile.php');

try {
    $user = parse_ini_file('../ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
  
    $ss = new SharedShelfService($user['email'], $user['password']);
  
    if ($ss->logged_in()) {
      echo "User is logged in\n";
    }
  
    $project = 4803; // Seneca Haudenosaunee
    $starting_asset = 22702858;

    $map = new SharedShelfMetadataApplicationProfile($ss);

    $map->set_project($project);

    $asset = $map->get_asset($starting_asset);
    


    // $id = $starting_asset;
    // for ($i = 0; $i < $max_processing_count; $i++) {
    //   $asset = $ss->asset($id);
    //   $url = $ss->media_url($id);
    //   $extension = $ss->media_file_extension($id);
    //   $iiif = $ss->media_iiif_info($id);
    //    echo "\n\n************************** Asset: $id *********************************\n";
    //   print_r($asset);
    //   print_r(array('media url', $url));
    //   print_r(array('extension', $extension));
    //   print_r(array('iiif', $iiif));
    //   if ($include_metadata) {
    //     $metadata = $ss->project_metadata($asset['project_id']);
    //     print_r(array('metadata', $metadata));
    //   }
    //   $id++;
    // }
  }
  catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
  