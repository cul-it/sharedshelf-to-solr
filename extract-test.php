<?php
// extract-test.php - testing extract of text from doc, pdf etc.

require_once('SolrUpdater.php');
require_once('SharedShelfToSolrLogger.php');

try {

  $ini = 'extract-test.ini';
  $vars = parse_ini_file($ini);
  $dev_solr = $vars['solr'];
  $project = $vars['project'];
  $solr = new SolrUpdater($dev_solr, $ini);

  // reload changes in schema and config
  $solr->reload('dev');

  // confirm current contents of doc1
  $doc1 = $solr->get_item('doc1');
  print_r($doc1);

  // $q="literal.id=doc2&stream.file=/cul/app/solr/solr-6.3.0/example/exampledocs/solr-word.pdf&stream.contentType=application/pdf&wt=json&debugQuery=on";
  // $json = $solr->raw_get('/update/extract', $q);
  // $result = json_decode($json);
  // print_r(array('result', 'json' => $json, 'php' => $result));
  $solr->extract('doc2', 'http://stor.artstor.org/stor/6a1f61b7-44f8-4422-aa51-6c96f7123aa9.pdf');

  $solr->commit();

  $doc2 = $solr->get_item('doc2');
  print_r($doc2);
}
catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);
