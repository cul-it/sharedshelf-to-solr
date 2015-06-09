<?php
class SolrUpdater {

  private $solr_url = '';

  /**
   * constructor
   * @param string $solr URL of the solr service
   * eg. http://jrc88.solr.library.cornell.edu/solr/
   */
  function __construct($solr) {
    $this->solr_url = $solr;
  }

}
