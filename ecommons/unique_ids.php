<?php
require_once('eCommonsService.php');

function add_id($id, $type, &$list) {
    if (isset($list["$type"])) {
        $list["$type"][] = $id;
    }
    else {
        $list["$type"] = array($id);
    }
}

try {
    $ecommons = new eCommonsService();

    $ids = array();
    $communities = $ecommons->get_response('/communities', FALSE);
    $indent = '    ';
    $max_items_per_collection = 0;
    $item_count = 0;
    foreach ($communities as $id => $community) {
        add_id($community['id'], 'community', $ids);
        echo $community['name'] . PHP_EOL;
        $collections = $ecommons->get_response('/communities/' . $community['id'] . '/collections');
        foreach ($collections as $col) {
            add_id($col['id'], 'collection', $ids);
            $items = $ecommons->get_response('/collections/' . $col['id'] . '/items', FALSE);
            $num_items = count($items);
            $max_items_per_collection = max( $max_items_per_collection, $num_items);
            $item_count += $num_items;
            if (!empty($items)) {
                foreach( $items as $item) {
                    add_id($item['id'], 'item', $ids);                   
                }
            }
            else {
                echo $indent . $indent . "no items\n";
            }
            echo $indent . $col['id'] . ' ' . $col['name'] . " ($num_items)" . PHP_EOL;
        }
    }
    $keys = array_keys($ids);
    foreach($keys as $key) {
        $ids["$key"] = array_unique($ids["$key"], SORT_NUMERIC);
        sort($ids["$key"], SORT_NUMERIC);
        $n = count($ids["$key"]);
        echo "$key: $n \n";
    }
    echo "Max items per collection: $max_items_per_collection \n";
    echo "Total items: $item_count \n";
    //print_r($ids);

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
