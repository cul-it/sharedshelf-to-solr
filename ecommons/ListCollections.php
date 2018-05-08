<?php
require_once('eCommonsService.php');

try {
    $ecommons = new eCommonsService();

    $communities = $ecommons->get_response('/communities', FALSE);
    $indent = '    ';
    foreach ($communities as $id => $community) {
        echo $community['uuid'] . ' ' . $community['name'] . PHP_EOL;
        $collections = $ecommons->get_response('/communities/' . $community['uuid'] . '/collections');
        print_r($community);
        print_r($collections);
        die('here');
        foreach ($collections as $col) {
            echo $indent . $col['id'] . ' ' . $col['name'] . PHP_EOL;
        }
    }

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

