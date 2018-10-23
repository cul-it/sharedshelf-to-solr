<?php
// compare-ss-counts.php - project counts for different solrs

ini_set('memory_limit', '512M');

require_once('SharedShelfService.php');
require_once('SolrUpdater.php');

$jrc88 = "http://jrc88.solr.library.cornell.edu/solr/digitalcollections";
$digcoll = "http://digcoll.library.cornell.edu/solr/digitalcollections2";
$ini = "compare-ss-counts-sample.ini";

try {
    // batch process information
    $task = parse_ini_file("sharedshelf-to-solr.ini", TRUE);
    if ($task === FALSE) {
        echo "Need sharedshelf-to-solr.ini\n";
        exit (1);
        }
    // sharedshelf user
    $user = parse_ini_file('ssUser.ini');
    if ($user === FALSE) {
        throw new Exception("Need to create ssUser.ini. See README.md", 1);
        }

    if (!isset($task['process']['cookie_jar_path'])) {
        throw new Exception("Expecting cookie_jar_path in .ini file", 1);
        }

    $ss = new SharedShelfService($user['email'], $user['password'], $task['process']['cookie_jar_path']);

    $projects = $ss->projects();

    $config = "compare-ss-counts-sample.ini";
    $solr1 = new SolrUpdater($jrc88, $config);
    $solr2 = new SolrUpdater($digcoll, $config);

    echo "id,project,jrc88,digcoll2\n";

    foreach ($projects['items'] as $project) {
        $id = $project['id'];
        $name = $project['name'];

        $ids = $solr1->get_all_ids($id);
        $count1 = count($ids);

        $ids = $solr2->get_all_ids($id);
        $count2 = count($ids);

        if ($count1 != $count2) {
            echo implode(',', array($id, '"' . $name .'"', $count1, $count2)) . "\n";
        }

    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
