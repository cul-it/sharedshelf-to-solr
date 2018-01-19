<?php
require_once('eCommonsService.php');

function add_id($id, $type, &$list) {
    if (isset($list["$id"])) {
        $list["$id"][] = $type;
    }
    else {
        $list["$id"] = array($type);
    }
}

try {
    $ecommons = new eCommonsService();

    $ids = array();
    $communities = $ecommons->get_response('/communities', FALSE);
    $indent = '    ';
    foreach ($communities as $id => $community) {
        add_id($community['id'], 'community', $ids);
        echo $community['name'] . PHP_EOL;
        $collections = $ecommons->get_response('/communities/' . $community['id'] . '/collections');
        foreach ($collections as $col) {
            add_id($col['id'], 'collection', $ids);
            echo $indent . $col['id'] . ' ' . $col['name'] . PHP_EOL;
            $items = $ecommons->get_response('/items/' . $col['id'], FALSE);
            if (!empty($items)) {
                add_id($items['id'], 'item', $ids);
            }
            else {
                echo $indent . $indent . "no items\n";
            }
        }
    }
    print_r($ids);

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
