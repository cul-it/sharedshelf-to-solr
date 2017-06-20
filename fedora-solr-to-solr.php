<?php
/**
 * fedora-solr-to-solr.php - copy Fedora solr documents into the portal solr
 */

require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

$fedora_ini = 'fedora-solr.ini';
$portal_ini = 'fedora-portal-solr.ini';

try {
  $vars = parse_ini_file($fedora_ini);
  $fedora_solr = $vars['solr'];
  $project = $vars['project'];
  $solr_in = new SolrUpdater($fedora_solr, $fedora_ini);

  $vars = parse_ini_file($portal_ini);
  $portal_solr = $vars['solr'];
  $solr = new SolrUpdater($portal_solr, $portal_ini);

  $ids = $solr_in->get_all_ids_prefix_type('chla', 'Book', 0, 10);

  $assets = array();
  foreach ($ids as $id) {
    $doc = $solr_in->get_item($id);
    $assets[] = $doc;
    echo 'id: ' . $doc['id'] . PHP_EOL;
  }

  $solr->add_without_custom($assets);

}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);
