<?php
// genMAPFieldList.php - list all fields

ini_set('memory_limit', '512M');

require_once '../SharedShelfService.php';
require_once 'SharedShelfMetadataApplicationProfile.php';


try {
    $user = parse_ini_file('../ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
  
    $ss = new SharedShelfService($user['email'], $user['password']);
  
    if (!$ss->logged_in()) {
      throw new Exception("Can not log in.", 1);
    }
    
    $ssmap = new SharedShelfMetadataApplicationProfile($ss);

    $fields = $ssmap->getMAPFields();
    $map_fields = [];
    $max_numbered_field = 5;
    foreach ($fields as $key => $val) {
        $map = $val['map_name'];
        $solr_field = $val['solr_name'];
        if ($val['multivalued'] == 1) {
            // deal with numbered field names

            // multiple MAP names seem to have underscores instead of blanks
            $map = str_replace(' ', '_', $map);
            $map_fields["$map"] = $solr_field;

            $count = preg_match('/\_[a-z]+$/', $solr_field, $matches);
            $solr_ext = $matches[0] ?? '';
            $solr_prefix = substr($solr_field, 0, strlen($solr_field) - strlen($solr_ext));
            $count = preg_match('/\_[a-zA-Z]+$/', $map, $matches);
            $map_ext = $matches[0] ?? '';
            $map_prefix = substr($map, 0, strlen($map) - strlen($map_ext));
            for ($i = 2; $i <= $max_numbered_field; $i++) {
                $solr_num = $solr_prefix . $i . $solr_ext;
                $map_num = $map_prefix . $i . $map_ext;
                $map_fields["$map_num"] = $solr_num;
            }
        }
        else {
            $map_fields["$map"] = $solr_field;
        }
    }

    $out = ['$info = ['];
    foreach ($map_fields as $map => $jf) {
        $out[] = sprintf("[%-45s,'%s' ],", "'$map'", $jf);
    }
    $out[] = '];';
    $out[] = '';
    echo implode("\n", $out);
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
