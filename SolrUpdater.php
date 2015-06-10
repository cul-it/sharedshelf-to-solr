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
    $this->ini = $vars;
    if (empty($this->ini['project'])) {
      throw new Exception("ini file must contain a project id", 1);
    }
    if (empty($this->ini['copy_field'])) {
      throw new Exception("ini file must contain copy_field", 1);
    }
    if (empty($this->ini['set_solr_field'])) {
      throw new Exception("ini file must contain set_solr_field", 1);
    }
  }

  function post_json($url_suffix = '/admin/info/system', $json) {
    $url = $this->solr_url . $url_suffix;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json))
    );
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result === FALSE) {
      throw new Exception("Error Processing Request: " . $url, 1);
    }
    return $result;
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
        continue; // skip any unknown fields
        //throw new Exception("Missing mapping from field: $ss_field to it's solr field name", 1);
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

  function add_custom_fields(&$asset) {
    if (isset($this->ini['copy_field'])) {
      foreach($this->ini['copy_field'] as $ss_solr_key => $solr_key) {
        $asset["$solr_key"] = $asset["$ss_solr_key"];
      }
    }
    if (isset($this->ini['set_solr_field'])) {
      foreach($this->ini['set_solr_field'] as $solr_key => $value) {
        $asset["$solr_key"] = $value;
      }
    }
  }

  function update($assets) {
    $json = '';
    foreach ($assets as $asset) {
      $this->add_custom_fields($asset);
      $json .= $this->format_update_asset_field_values($asset);
    }
    $this->post_json('/update/json', $json);
  }

  function add($assets) {
    $json = '';
    foreach ($assets as $asset) {
      $this->add_custom_fields($asset);
      $json .= $this->format_add_asset_field_values($asset);
    }
    $this->post_json('/update/json', $json);
  }

}
