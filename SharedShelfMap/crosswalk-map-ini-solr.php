<?php
// crosswalk MAP fields to the current solr fields from current .ini files

ini_set('memory_limit', '512M');

require_once '../SharedShelfService.php';
require_once 'SharedShelfMetadataApplicationProfile.php';
require_once '../ss2solr-field-list.php';


try {
    $user = parse_ini_file('../ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
  
    $ss = new SharedShelfService($user['email'], $user['password']);
  
    if (!$ss->logged_in()) {
      throw new Exception("Can not log in.", 1);
    }
 
    // get solr fields from .ini files 
    $solr_field_list = getSolrFieldList();
    
    $map = new SharedShelfMetadataApplicationProfile($ss);

    $mapFields = $map->mapSolrFields();
    foreach ($mapFields as $line) {
        //echo $line;
        list($map,$solr,$multi) = explode(',', $line);
        $isnew = isset($solr_field_list["$solr"]) ? 'recycled' : 'new';
        echo implode(',', array($map,$solr,trim($multi),$isnew)) . "\n";
    }

    // $fields = $map->listFields();

    // // remove any map_ prefixes from these
    // $map_fields = array();
    // foreach($fields as $field) {
    //     $map_fields[] = preg_replace('/^map_/','',$field);
    // }
    // sort($map_fields);
     

    // $cross = array();
    // $missing_in_solr = array();
    // $present_in_solr = array();
    // foreach ($mapFields as $map) {
    //     $solr = $map['solr'];
    //     $map['new_solr'] = isset($solr_field_list["$solr"]) ? $solr : '';
    //     $map['map'] = '"' . $map['map'] . '"';
    //     $cross[] = implode(',', $map);
    // }

    // echo "\n\nMAP and solr .ini files both have these fields:\n\n";
    // foreach($present_in_solr as $field) {
    //     echo "$field\n";
    // }

    // echo "\n\nSolr .ini files are missing these fields:\n\n";
    // foreach($missing_in_solr as $field) {
    //     echo "$field\n";
    // }
    // print_r($cross);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
