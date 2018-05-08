<?php
// generate-collection-list.php - write out the ids of ecommons collections

require_once('eCommonsService.php');

try {
    $ecommons = new eCommonsService();

    $collection_ids = array();
    $pagesize = 10;
    for ($offset = 0; ; $offset += $pagesize) {
        $paginate = "offset=$offset&limit=$pagesize";
        $communities = $ecommons->get_response("/communities?$paginate", FALSE);
        if (empty($communities) || count($communities) < 1) break;
        foreach ($communities as $community) {
            $pagesize2 = 100;
            for ($offset2 = 0; ; $offset2 += $pagesize2) {
                $paginate2 = "offset=$offset2&limit=$pagesize2";
                $collections = $ecommons->get_response('/communities/' . $community['uuid'] . "/collections?$paginate2");
                if (empty($collections) || count($collections) < 1) break;
                foreach ($collections as $col) {
                    $collection_ids[] = $col['uuid'];
                }
            }
        }
    }

    $result = sort($collection_ids);
    foreach ($collection_ids as $id) {
        echo $id . PHP_EOL;
    }

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

