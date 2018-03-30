<?php
// generate-collection-list.php - write out the ids of ecommons collections

require_once('eCommonsService.php');

try {
    $ecommons = new eCommonsService();

    $communities = $ecommons->get_response('/communities', FALSE);
    $collection_ids = array();
    foreach ($communities as $id => $community) {
        $collections = $ecommons->get_response('/communities/' . $community['id'] . '/collections');
        foreach ($collections as $col) {
            $collection_ids[] = $col['id'];
        }
    }

    $result = sort($collection_ids, SORT_NUMERIC);
    foreach ($collection_ids as $id) {
        echo $id . PHP_EOL;
    }

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

