<?php
// solr-field-list.php - list solr fields referenced in ss2solr.xxx.ini fields

function addName($solr, $config, &$solrNames) {
    if (isset($solrNames["$solr"])) {
        $solrNames["$solr"][] = $config;
    }
    else {
        $solrNames["$solr"] = array($config);
    }
}

function getSolrFieldList() {

    // unique names and where defined
    $solrNames = array();

    try {

        // batch process information
        $task = parse_ini_file("sharedshelf-to-solr.ini", TRUE);
        if ($task === FALSE) {
        echo "Need sharedshelf-to-solr.ini\n";
        exit (1);
        }
        
        foreach($task['configuration_files']['config'] as $config) {
            $project = parse_ini_file($config);
            if ($project === FALSE) {
            throw new Exception("Missing configuration file: $config", 1);
            }
            //print_r ($project);
            foreach ($project['fields'] as $ss => $solr) {
                addName($solr, $config, $solrNames);
            }
            foreach ($project['set_solr_field'] as $solr => $text) {
                addName($solr, $config, $solrNames);
            }
            if (isset($project['set_single_value'])) {
                foreach ($project['set_single_value'] as $solr => $text) {
                    addName($solr, $config, $solrNames);
                }
            }
        }
        ksort($solrNames);
        // foreach ($solrNames as $solr => $iniFiles) {
        //     $unique = count(array_unique($iniFiles));
        //     echo "$solr,$unique\n";
        // }
        //print_r($solrNames);
     }
    catch (Exception $e) {
        $error = 'Caught exception: ' . $e->getMessage() . "\n";
        echo $error;
    }
    return $solrNames;
}
