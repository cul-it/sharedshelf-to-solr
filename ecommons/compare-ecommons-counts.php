<?php
// compare-ecommons-counts.php - project counts for different solrs

ini_set('memory_limit', '512M');

require_once('../SolrUpdater.php');

$jrc88 = "http://jrc88.solr.library.cornell.edu/solr/digitalcollections";
$digcoll = "http://digcoll.library.cornell.edu/solr/digitalcollections2";

try {
    // batch process information
    $task = parse_ini_file("../sharedshelf-to-solr.ini", TRUE);
    if ($task === FALSE) {
        echo "Need sharedshelf-to-solr.ini\n";
        exit (1);
        }

    $config = "../compare-ss-counts-sample.ini";
    $solr_in = new SolrUpdater($jrc88, $config);
    $solr2 = new SolrUpdater($digcoll, $config);

    $select = "solr_loader_tesim:eCommons";
    $count = $solr_in->get_count($select);
    $count2 = $solr2->get_count($select);

    $per_call = 10;

    echo "Selecting: $select\n";
    if ($count == $count2) {
        throw new Exception("Matching counts - yay!", 1);      
    }
    echo "jrc88: $count, digcoll2: $count2\n";
    $dif = (int)$count - (int)$count2;
    echo "Diffence in eCommons asset counts: $dif\n";
    echo "Processing $count documents from $jrc88 at $per_call per call.\n";

    // $select = 'solr_loader_tesim:eCommons&facet=on&facet.field=collection_tesim';
    // $assets = $solr_in->get_documents(0, 10, $select);
    // var_dump($assets);
    // die('here');
  
    $missing = array();
    $start = 0;
    $assets = $solr_in->get_documents($start, $per_call, $select);
    while (count($assets) > 0 && count($missing) < 10) {

        for ($i = 0; $i < count($assets); $i++) {
            $id = $assets[$i]['id'];
            $item = $solr2->get_item($id);
            if (empty($item)) {
                $missing[] = $id;
                echo '.';
            }
        }
    $start += $per_call;
    $assets = $solr_in->get_documents($start, $per_call, $select);
    }

    echo "Example missing documents:\n";
    foreach ($missing as $id) {
        echo "$id\n";
    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
