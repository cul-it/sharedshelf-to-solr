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

    // get collection info
    $collection = $ecommons->get_response('/collections/' . $single_collection);
    if (empty($collection)) {
        throw new Exception("No info for collection $single_collection", 1);               
    }
    if (empty($collection['numberItems'])) {
        throw new Exception("Collection $single_collection has no items", 1);               
    }
    $numberItems = $collection['numberItems'];

    $pagecount = 10;
    for ($offset = 0; $offset < $numberItems; $offset += $pagecount) {
        $items = $ecommons->get_response("/collections/$single_collection/items?limit=$pagecount&offset=$offset");
        if (empty($items)) {
            throw new Exception("No items from offset $offset on collection $single_collection", 1);
        }
        foreach ($items as $item) {
            $metadata = $metadata = $ecommons->get_response('/items/' . $item['id'] . '/metadata');
            if (empty($metadata)) {
                throw new Exception("No metadata for item " . $item['id'], 1);               
            }
            print_r(array('item' => $item, 'metadata' => $metadata));
        }
    }
    die('here');
    
    $metadata = $ecommons->get_response('/items/' . $single_collection . '/metadata', FALSE);
    if (empty($metadata)) {
        throw new Exception("No metadata for item $single_collection", 1);               
    }

    $items = $ecommons->get_response('/items/' . $single_collection, FALSE);
    if (empty($items)) {
        throw new Exception("No metadata for item $single_collection", 1);               
    }
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

