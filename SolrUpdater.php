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

  function format_update_asset_field_values($asset) {
    /*
    convert
    $asset['id'] = nnn
    $asset['ss_field_1'] = value1
    $asset['ss_field_2'] = value2
    ...
    to JSON atomic update
    [
      {
        "id" : "nnn",
        "solr_field_1" : {"set":"value1"},
        "solr_field_2" : {"set":"value2"},
        ...
      }
    ]
    */
    /*
    note: if an update field does not exist solr will throw an error
    */
    if (empty($this->ini['fields'])) {
      throw new Exception("ini file must contain fields", 1);
    }
    $out = array();
    $fields = $this->ini['fields'];
    foreach ($asset as $ss_field => $value) {
      if (empty($fields["$ss_field"])) {
        throw new Exception("Missing mapping from field: $ss_field to it's solr field name", 1);
      }
      if (empty($value)) {
        $value = NULL; // for clearing previous values
      }
      $solr_field = $fields["$ss_field"];
      if ($ss_field == 'id') {
        $out["$solr_field"] = $value;
      }
      else {
        // could be set, add, or inc
        $out["$solr_field"] = (object) array('set' => $value);
      }
    }
    $obj = (object) $out;
    return json_encode($obj);
  }

  function format_add_asset_field_values($asset) {
    $data2 = array('add' => array( 'doc' => $asset, 'commitWithin' => 1000,),);
    return json_encode($data2);
  }

}
