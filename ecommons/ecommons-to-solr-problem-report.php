<?php
// ecommons-to-solr.php - migrate metadata from ecommons to solr

ini_set('memory_limit', '512M');

// to make relative paths work in require
chdir( dirname ( __FILE__ ) );

require_once('eCommonsService.php');
require_once('../SolrUpdater.php');

define("ECOMMONS_ID_PREFIX", 'ec:');

class DatesMatchException extends Exception {}

function debug($item, $description = '', $die = TRUE) {
    if (!empty($description)) {
        print PHP_EOL . 'DEBUG: ' . $description . PHP_EOL;
    }
    print_r($item);
    if ($die) {
        die('debugging' . PHP_EOL);
    }
}

function usage() {
    global $argv;
    echo PHP_EOL;
    echo "Usage: php " . $argv[0] . " [--help] [--force] [--no-write] [-c NNN] [-s NNN] [-n NNN]" . PHP_EOL;
    echo "--help - show this info" . PHP_EOL;
    echo "-c - only process eCommons collection (collection number) NNN (NNN must be numeric) - see listCollections.php" . PHP_EOL;
    echo "-s - start processing at the given eCommons item number NNN (NNN must be numeric) (item numbers ascend during processing)" . PHP_EOL;
    echo "-n - process only this many (integer) items" . PHP_EOL;
    echo "with no arguments, it reads all collections from collection-list.txt and processes them all\n";
    exit (0);
}

function flatten($asset_array) {
    $asset = array();
    foreach($asset_array as $item) {
        $key = $item['key'];
        if (isset($asset["$key"])) {
            if (is_array($asset["$key"])) {
                $asset["$key"][] = $item['value'];
            }
            else {
                $asset["$key"] = array($asset["$key"], $item['value']);
            }
        }
        else {
            $asset["$key"] = $item['value'];
        }
    }
    return $asset;
}

$options = getopt("c:s:n:",array("help"));

if ($options === false || isset($options['help'])) {
    usage();
}
$force_replacement = isset($options["force"]);
$do_not_write_to_solr = isset($options["no-write"]);
if (isset($options['c'])) {
    $single_collection = $options['c'];
}
else {
    $single_collection = FALSE;
}
if (isset($options['s'])) {
    if (is_numeric($options['s'])) {
        $starting_asset = $options['s'];
    }
    else {
        usage();
    }
}
else {
    $starting_asset = 0;
}
if (isset($options['n'])) {
    if (is_numeric($options['n'])) {
        $max_processing_count = $options['n'];
    }
    else {
        usage();
    }
}
else {
    $max_processing_count = false; // this means process them all
}


try {

    // load the mapping
    $config = "ecommons.ini";
    $task = parse_ini_file($config, TRUE);
    if ($task === FALSE) {
        throw new Exception("Need configuration file: $config", 1);
    }
    
    // connect to solr
    $solr_url = $task['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    $ecommons = new eCommonsService();

    if ($single_collection !== FALSE) {
        $project_ids = array($single_collection);
    }
    else {
        $filename = 'collection-list.txt';
        $project_ids = file($filename, FILE_IGNORE_NEW_LINES);
        if ($project_ids === FALSE) {
            throw new Exception("Can't read $filename", 1);       
        }
    }

    foreach ($project_ids as $key => $project_id) {

        // get collection info
        $collection = $ecommons->get_response('/collections/' . $project_id );
        if (empty($collection)) {
            throw new Exception("No info for collection $project_id ", 1);               
        }
        $numberItems = empty($collection['numberItems']) ? 0 : $collection['numberItems'];
        $collectionName = $collection['name'];
        echo "\nId: $project_id Items: $numberItems Collection name: $collectionName";
        if (empty($collection['numberItems'])) {
            $output = "\nProblem: Collection $project_id  has no items - $collectionName";
            echo $output;
            continue;               
        }

        $pagecount = min(10, $numberItems);
        for ($offset = 0; $offset < $numberItems; $offset += $pagecount) {
            $items = $ecommons->get_response("/collections/$project_id/items?limit=$pagecount&offset=$offset");
            if (empty($items)) {
                throw new Exception("No items from offset $offset on collection $single_collection", 1);
            }
            foreach ($items as $item) {
                $asset_id = $item['uuid'];
                $solr_id = ECOMMONS_ID_PREFIX . $asset_id;

                $asset = $ecommons->get_response('/items/' . $asset_id . '/metadata');
                if (empty($asset)) {
                    throw new Exception("No metadata for item $asset_id", 1);               
                }
                
                $asset = flatten($asset);

                // find problem records
                if (empty($asset['dc.date.accessioned'])) {
                    $output = "\nProblem: eCommons item id: $asset_id - ";
                    $output .= "Missing dc.date.accessioned field";
                    echo $output;
                }
                elseif (is_array($asset['dc.date.accessioned'])) {
                    $output = "\nProblem: eCommons item id: $asset_id - ";
                    $output .= "Multiple dc.date.accessioned fields";
                    echo $output;
                }
                // date format must be 2006-09-13T23:08:42Z
                elseif (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/',$asset['dc.date.accessioned']) != 1) {
                    $output = "\nProblem: eCommons item id: $asset_id - ";
                    $output .= "Bad date format: " . $asset['dc.date.accessioned'];
                    echo ($output);
                }
            }
        }
    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}