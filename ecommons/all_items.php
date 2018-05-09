<?php
require_once('eCommonsService.php');

try {
    $ecommons = new eCommonsService();

    $ids = array();
    $items = $ecommons->get_response('/items', FALSE);
    print_r($items);
    echo "Total items: " . count($items) . PHP_EOL;
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
