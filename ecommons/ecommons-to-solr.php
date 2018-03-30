<?php
// ecommons-to-solr.php - migrate metadata from ecommons to solr

ini_set('memory_limit', '512M');

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
    echo "--force - ignore timestamps and rewrite all solr records" . PHP_EOL;
    echo "--no-write - do everything EXCEPT writing the solr records" . PHP_EOL;
    echo "-c - only process eCommons collection (collection number) NNN (NNN must be numeric) - see listCollections.php" . PHP_EOL;
    echo "-s - start processing at the given eCommons item number NNN (NNN must be numeric) (item numbers ascend during processing)" . PHP_EOL;
    echo "-n - process only this many (integer) items" . PHP_EOL;
    exit (0);
}

function flatten($asset_array) {
    $asset = array();
    foreach($asset_array as $item) {
        $key = $item['key'];
        $asset["$key"] = $item['value'];
    }
    return $asset;
}

$options = getopt("c:s:n:",array("help", "force", "no-write"));

if ($options === false || empty($options) || isset($options['help'])) {
    usage();
}
$force_replacement = isset($options["force"]);
$do_not_write_to_solr = isset($options["no-write"]);
if (isset($options['c'])) {
    if (is_numeric($options['c'])) {
        $single_collection = $options['c'];
    }
    else {
        usage();
    }
}
else {
    usage();
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
    $project_id = $single_collection;

    // load the mapping
    $config = "ecommons.ini";
    $task = parse_ini_file($config, TRUE);
    if ($task === FALSE) {
        throw new Exception("Need configuration file: $config", 1);
    }
    
    // connect to solr
    $solr_url = $task['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    // list of the ids already in solr
    $solr_asset_id_list = $solr->get_all_ids($project_id);
    $solr_asset_ids_to_delete = array_flip($solr_asset_id_list);
    echo("Ids in solr for project $project_id: " . count($solr_asset_id_list) . PHP_EOL);

    $ecommons = new eCommonsService();

    // get collection info
    $collection = $ecommons->get_response('/collections/' . $project_id );
    if (empty($collection)) {
        throw new Exception("No info for collection $project_id ", 1);               
    }
    if (empty($collection['numberItems'])) {
        throw new Exception("Collection $project_id  has no items", 1);               
    }
    $numberItems = $collection['numberItems'];
    echo("Items in collection: $numberItems\n");
    $collectionName = $collection['name'];
    echo("Collection name: $collectionName\n");
    
    $pagecount = min(10, $numberItems);
    for ($offset = 0; $offset < $numberItems; $offset += $pagecount) {
        $items = $ecommons->get_response("/collections/$project_id/items?limit=$pagecount&offset=$offset");
        if (empty($items)) {
            throw new Exception("No items from offset $offset on collection $single_collection", 1);
        }
        foreach ($items as $item) {
            $asset_id = $item['id'];
            $solr_id = ECOMMONS_ID_PREFIX . $asset_id;
            // track ids dropped from ecommons
            unset($solr_asset_ids_to_delete["$solr_id"]);

            $asset = $ecommons->get_response('/items/' . $asset_id . '/metadata');
            if (empty($asset)) {
                throw new Exception("No metadata for item $asset_id", 1);               
            }
            echo("Solr id: $solr_id\n");
            $asset = flatten($asset);
            $merged = array_merge($asset, $item); // item and metadata in one flat array
            $solr_out = $solr->convert_ss_names_to_solr($merged);
            $solr_out['id'] = $solr_id; // switch to solr id from ecommons
            $solr_out['collection_tesim'] = $collectionName; // can not use set_solr_field[collection_tesim]
            $solr_assets = array($solr_out);
            $result = $solr->add($solr_assets);
        }
    }

    if (!empty($solr_asset_ids_to_delete)) {
        $ids = array_flip($solr_asset_ids_to_delete);
        echo("Deleting solr ids: " . implode(', ', $ids));
        $solr->delete_items($ids);
    }
    else {
        echo("No solr asssets to delete for project $project_id.\n");
    }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}