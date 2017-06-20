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

$selection = 'has_model_ssim:(-Hydra* -ActiveFedora*)';

try {
  $vars = parse_ini_file($fedora_ini);
  $fedora_solr = $vars['solr'];
  $project = $vars['project'];
  $solr_in = new SolrUpdater($fedora_solr, $fedora_ini);

  $vars = parse_ini_file($portal_ini);
  $portal_solr = $vars['solr'];
  $solr = new SolrUpdater($portal_solr, $portal_ini);

  $log = new SharedShelfToSolrLogger('Fedora-to-solr');
  $log->task('Import_All');

  $count = $solr_in->get_count($selection);
  $per_call = 10;
  $log->note("asset_count:$count at $per_call per call");

  echo "Processing: $project asset count: $count " . $log->log_file_name() . PHP_EOL;

  $counter = 0;
  for ($start = 0; $start < $count; $start += $per_call) {

    $ids = $solr_in->get_ids($start, $per_call, $selection);

    $assets = array();
    foreach ($ids as $id) {
      $doc = $solr_in->get_item($id);
      $assets[] = $doc;
      echo 'id: ' . $doc['id'] . PHP_EOL;
    }

    $solr->add_without_custom($assets);

    $log->item("Last item $id");
    $pct = sprintf("%01.2f", $counter++ * 100.0 / (float) $count);
    $log->note("Completed:$pct");
  }
  $log->task('Done.');
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);
