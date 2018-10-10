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

    /**
      * 
      * These projects use the new MAP:
      * 4803 - Seneca Haudenosaunee
      * 9175 - 01 PROJECT TEMPLATE
      * 4547 - NYS Historical Dendrochronology
      *
      */
  
    $collection = 1;
    if ($collection == 1) {
      $project = 4803; // Seneca Haudenosaunee
      $starting_asset = 22702858;
    }
    elseif ($collection == 2) {
      #
      $project = 4547; // NYS Historical Dendrochronology
      $starting_asset = 22143997;
    }
    else {
      $project = 9175; // 01 PROJECT TEMPLATE
      $starting_asset = 23091706;
    }
    

    $map = new SharedShelfMetadataApplicationProfile($ss);

    $map->set_project($project);

    // $ssMap = $map->get_map();
    // print_r($ssMap);

    // $fields = $map->get_raw_fields();
    // print_r($fields);

    // $asset = $map->get_asset($starting_asset);
    
    // print_r($asset);

    $ini = $map->get_map_as_ini();
    echo $ini;


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
  