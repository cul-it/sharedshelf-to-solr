<?php
class SolrUpdater {

  private $solr_url = '';
  private $ini = array();

  /**
   * constructor
   * @param string $solr URL of the solr service
   * @param string $ini_file path to .ini file containing field
   * eg. http://jrc88.solr.library.cornell.edu/solr/
   */
  function __construct($solr, $ini_file) {
    $this->solr_url = $solr;
    $vars = parse_ini_file($ini_file);
    if ($vars === FALSE) {
      throw new Exception("ini_file $ini_file is not readable.", 1);
    }
    if (empty($vars['project'])) {
      throw new Exception("ini file must contain a project id", 1);
    }
    $this->ini = $vars;
  }

  }

}
