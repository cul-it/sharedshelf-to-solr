<?php
require_once('eCommonsService.php');

try {
    $user = parse_ini_file('ecommons.ini');

    $ecommons = new eCommonsService();

    $test = $ecommons->get_response('/test');
    print_r($test);

    $communities = $ecommons->get_response('/communities');
    print_r($communities);

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

