<?php
// copy-solr-to-solr.php - copy selected documents from one to another

ini_set('memory_limit', '512M');

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');

$jrc88 = "http://jrc88.solr.library.cornell.edu/solr/digitalcollections";
$digcoll = "http://digcoll.library.cornell.edu/solr/digitalcollections2";

try {
    // batch process information
    $task = parse_ini_file("sharedshelf-to-solr.ini", TRUE);
    if ($task === FALSE) {
        echo "Need sharedshelf-to-solr.ini\n";
        exit (1);
        }

    $config = "compare-ss-counts-sample.ini";
    $solr_in = new SolrUpdater($jrc88, $config);
    $solr = new SolrUpdater($digcoll, $config);

    $select = "collection_tesim:\"Wordsworth Collection\"";

    $count = $solr_in->get_count($select);

    $per_call = 10;

    echo "Selecting: $select";
    echo "Processing $count documents from $jrc88 at $per_call per call.\n";
  
    $start = 0;
    $assets = $solr_in->get_documents($start, $per_call, $select);
    while (count($assets) > 0) {
  
        $id = $assets[0]['id'];
        echo "example id: $id - ";

        // avoid version conflict when copying from one solr to another
        for ($i = 0; $i < count($assets); $i++) {
        unset($assets[$i]['_version_']);
        }

        $solr->add_without_custom($assets);

        $pct = sprintf("%01.2f", $start * 100.0 / (float) $count);
        echo "Completed:$pct\n";

        $start += $per_call;
        $assets = $solr_in->get_documents($start, $per_call, $select);
    }        
    echo "Done.\n";
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
    