<?php
// ssDeleteProjectFromSolr.php - removes all assets from a project
require_once('SolrUpdater.php');

class ConsoleQuestion
{

    function readline()
    {
        return rtrim(fgets(STDIN));
    }
}

function usage() {
  global $argv;
  echo PHP_EOL;
  echo "Usage: php " . $argv[0] . " [--help] [-p NNN]" . PHP_EOL;
  echo "--help - show this info" . PHP_EOL;
  echo "-p - project number NNN (NNN must be numeric)" . PHP_EOL;
  exit (0);
}

$options = getopt("p:",array("help"));

if ($options === false || isset($options['help'])) {
  usage();
}
if (isset($options['p']) && is_numeric($options['p'])) {
    $project_to_delete = $options['p'];
  }
else {
  usage();
}


try {
  // batch process information
  $task = parse_ini_file("sharedshelf-to-solr.ini", TRUE);
  if ($task === FALSE) {
    echo "Need sharedshelf-to-solr.ini\n";
    exit (1);
  }

  $found = FALSE;
  foreach($task['configuration_files']['config'] as $config) {
    $project = parse_ini_file($config);
    if ($project === FALSE) {
      throw new Exception("Missing configuration file: $config", 1);
    }
    if ($project['project'] != $project_to_delete) {
      // skip any other collection if one is listed on the command line
      continue;
    }

    $found = TRUE;
    $project_id = $project['project'];

    $solr_url = $project['solr'];
    $solr = new SolrUpdater($solr_url, $config);

    $solr_project_field = 'project_id_ssi';

    $count = $solr->get_count("$solr_project_field:$project_id");
    $line = new ConsoleQuestion();
    echo "Delete all $count assets \nin project $project_id \nfrom  $solr_url? [no/yes] ";
    $answer = $line->readline();
    echo "You Entered: " . $answer . "\n";
    if (strcmp(strtolower($answer),'yes') === 0) {
      $query = "$solr_project_field:$project_id";
      $solr->delete_matching($query);
      $solr->commit();
      $count = $solr->get_count("$solr_project_field:$project_id");
      echo "New count: $count\n";
    }
    else {
      echo "Nothing deleted.\n";
    }
  }

  if (!$found) {
    throw new Exception("Could not find .ini file for project $project_to_delete", 1);       
  }

  
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>