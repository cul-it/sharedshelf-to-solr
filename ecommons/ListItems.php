<?php

require_once('eCommonsService.php');

function usage($message = '') {
    global $argv;
    echo PHP_EOL;
    if (!empty($message)) {
        echo "**************************\n";
        echo $message . PHP_EOL;
        echo "**************************\n";
    }
    echo "Usage: php " . $argv[0] . " [--help] -c NNN" . PHP_EOL;
    echo "--help - show this info" . PHP_EOL;
    echo "-c - only process eCommons collection (collection number) NNN (NNN must be numeric) - see listProjects.php" . PHP_EOL;
    exit (0);
  }
try {
    $options = getopt("c:",array("help", ));

    if ($options === false || isset($options['help'])) {
      usage();
    }
    if (isset($options['c'])) {
        if (is_numeric($options['c'])) {
          $single_collection = $options['c'];
        }
        else {
          usage();
        }
    }
    else {
        usage("The -c parameter is required");
    }

    $ecommons = new eCommonsService();

    $items = $ecommons->get_response('/items/' . $single_collection . '/metadata', FALSE);
    $fields = array();
    foreach ($items as $item) {
        $key = $item['key'];
        if (empty($fields["$key"])) {
            $fields["$key"] = array($item['value']);
        }
        else {
            $fields["$key"][] = $item['value'];
        }
    }
    ksort($fields);
    print_r($fields);
    print_r(array_keys($fields));
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

