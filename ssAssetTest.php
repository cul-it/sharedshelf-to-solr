<?php
// ssAssetTest.php - test sharedshelf object
require_once('SharedShelfService.php');
require_once('SolrUpdater.php');

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [-s NNN] [-n NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "-s - start processing at the given SharedShelf asset number NNN (NNN must be numeric) (asset numbers ascend during processing)" . PHP_EOL;
  echo "-n - process only this many (integer) assets" . PHP_EOL;
  exit (0);
}

$options = getopt("s:n:",array("help"));

if ($options === false || isset($options['help'])) {
  usage();
}
if (isset($options['s']) && is_numeric($options['s'])) {
    $starting_asset = $options['s'];
  }
else {
  usage();
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
  $max_processing_count = 1; // this means process just one
}


try {
  $user = parse_ini_file('ssUser.ini');
  if ($user === FALSE) {
    throw new Exception("Need to create ssUser.ini. See README.md", 1);
  }

  $ss = new SharedShelfService($user['email'], $user['password']);

  if ($ss->logged_in()) {
    echo "User is logged in\n";
  }

  $id = $starting_asset;
  for ($i = 0; $i < $max_processing_count; $i++) {
    $asset = $ss->asset($id);
    echo "\n\n************************** Asset: $id *********************************\n";
    print_r($asset);
    $id++;
  }
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
