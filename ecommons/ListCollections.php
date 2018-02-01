<?php
require_once('eCommonsService.php');

try {
    $ecommons = new eCommonsService();

    $test = $ecommons->get_response('/test', FALSE);
    print_r($test);

    $communities = $ecommons->get_response('/communities', FALSE);
    $indent = '    ';
    foreach ($communities as $id => $community) {
        echo $community['name'] . PHP_EOL;
        $collections = $ecommons->get_response('/communities/' . $community['id'] . '/collections');
        foreach ($collections as $col) {
            echo $indent . $col['id'] . ' ' . $col['name'] . PHP_EOL;
        }
    }

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
