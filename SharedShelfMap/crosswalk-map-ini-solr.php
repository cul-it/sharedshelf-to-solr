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
    
    $map = new SharedShelfMetadataApplicationProfile($ss);

    $fields = $map->listFields();

    // remove any map_ prefixes from these
    $map_fields = array();
    foreach($fields as $field) {
        $map_fields[] = preg_replace('/^map_/','',$field);
    }
    sort($map_fields);
     
    // get solr fields from .ini files 
    $solr_field_list = getSolrFieldList();

    $missing_in_solr = array();
    $present_in_solr = array();
    foreach ($map_fields as $map) {
        if (isset($solr_field_list["$map"])) {
            $present_in_solr[] = $map;
        }
        else {
            $missing_in_solr[] = $map;
        }
    }

    echo "\n\nMAP and solr .ini files both have these fields:\n\n";
    foreach($present_in_solr as $field) {
        echo "$field\n";
    }

    echo "\n\nSolr .ini files are missing these fields:\n\n";
    foreach($missing_in_solr as $field) {
        echo "$field\n";
    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
