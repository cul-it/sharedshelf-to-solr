<?php
/**
 * fedora-solr-to-solr.php - copy Fedora solr documents into the portal solr
 */

// has_model_ssim:("Page" OR "FileSet" OR "Article" OR "Issue" OR "Book" OR "Journal" OR "Collection")
// has_model_ssim:(Page OR FileSet OR Article OR Issue OR Book OR Journal OR Collection)
// has_model_ssim:(-Hydra* -ActiveFedora*)

require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

$fedora_ini = 'fedora-solr.ini';
$portal_ini = 'fedora-portal-solr.ini';

//$selection = 'has_model_ssim:(-Hydra* -ActiveFedora*)';
$selection = '(id:chla* OR id:hive*) AND (has_model_ssim:"Book" OR has_model_ssim:"Journal" OR has_model_ssim:"Article")';

try {

  $log = new SharedShelfToSolrLogger('Fedora-to-solr');
  echo "Log file: " . $log->log_file_name() . PHP_EOL;
  $log->task('Import_All');
  $log->item("First");

  $vars = parse_ini_file($fedora_ini);
  $fedora_solr = $vars['solr'];
  $project = $vars['project'];
  $solr_in = new SolrUpdater($fedora_solr, $fedora_ini);

  $vars = parse_ini_file($portal_ini);
  $portal_solr = $vars['solr'];
  $solr = new SolrUpdater($portal_solr, $portal_ini);

  $status = array("Project: $project", "Fedora: $fedora_solr", "Portal: $portal_solr", "Select: $selection");
  echo implode(PHP_EOL, $status) . PHP_EOL;

  $count = $solr_in->get_count($selection);

  $per_call = 10;
  $log->note("asset_count:$count at $per_call per call");

  echo "Processing: $project asset count: $count " . PHP_EOL;

  $start = 0;
  $assets = $solr_in->get_documents($start, $per_call, $selection);
  while (count($assets) > 0) {

    $id = $assets[0]['id'];
    $log->item("id: $id");

    // avoid version conflict when copying from one solr to another
    for ($i = 0; $i < count($assets); $i++) {
      unset($assets[$i]['_version_']);
    }

    $solr->add_without_custom($assets);

    $pct = sprintf("%01.2f", $start * 100.0 / (float) $count);
    $log->note("Completed:$pct");

    $start += $per_call;
    $assets = $solr_in->get_documents($start, $per_call, $selection);
  }
  $log->task('Done.');
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  $log->task('Done with ERROR.');
  exit (1);
}
exit (0);
