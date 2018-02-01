<?php
// ecommons-to-solr.php - migrate metadata from ecommons to solr

ini_set('memory_limit', '512M');

require_once('eCommonsService.php');
require_once('../SolrUpdater.php');
require_once('../SharedShelfToSolrLogger.php');

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

try {
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

    $ecommons = new eCommonsService();
    
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}