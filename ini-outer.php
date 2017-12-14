<?php


function usage() {
    global $argv;
    echo PHP_EOL;
    echo "Usage: php " . $argv[0] . " -t foo.ini" . PHP_EOL;
    echo "--help - show this usage info" . PHP_EOL;
    echo "-t - test this .ini file" . PHP_EOL;
    exit (0);
  }


$options = getopt("t:",array("help"));

if ($options === false || isset($options['help'])) {
  usage();
}

if (isset($options['t'])) {
    $file = $options['t'];
}
else {
    usage();
}

$task = parse_ini_file($file, TRUE);

print_r($task);