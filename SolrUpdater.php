<?php
class SolrUpdater {

  private $solr_url = '';
  private $ini = array();

  /**
   * constructor
   * @param string $solr URL of the solr service
   * @param string $ini_file path to .ini file containing field
   * eg. http://jrc88.solr.library.cornell.edu/solr
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

  private function post_json($url_suffix = '/admin/info/system', $json) {
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

  private function get($url_suffix = '/admin/info/system', $params) {
    $url = $this->solr_url . $url_suffix;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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
    $asset['solr_field_1'] = value1
    $asset['solr_field_2'] = value2
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
    foreach ($asset as $solr_field => $value) {
      if (empty($value) && $value !== FALSE) {
        $value = NULL; // for clearing previous values
      }
      if ($solr_field == 'id') {
        $out["$solr_field"] = $value;
      }
      else {
        // could be set, add, or inc
        $out["$solr_field"] = (object) array('set' => $value);
      }
    }
    $obj = (object) $out;
    $json = json_encode($obj);
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
    // $assets have names converted to solr already
    $json = '';
    foreach ($assets as $asset) {
      $this->add_custom_fields($asset);
      $json .= $this->format_update_asset_field_values($asset);
    }
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    if ($result->responseHeader->status != 0) {
      $err = print_r($result, TRUE);
      throw new Exception("Error Processing Request: $err", 1);
    }
    return $result;
  }

  function add($assets) {
    // $assets have names converted to solr already
    $json = '';
    foreach ($assets as $asset) {
      $this->add_custom_fields($asset);
      $json .= $this->format_add_asset_field_values($asset);
    }
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    if ($result->responseHeader->status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("Error Processing Request: $err", 1);
    }
    return $result;
  }

  function get_item($id) {
    // return array of field values for given solr item
    $q = "q=id:$id&wt=json";
    $json = $this->get('/collection1/select', $q);
    $result = json_decode($json);
    if ($result->response->numFound == 0) {
      return array();
    }
    elseif ($result->response->numFound == 1) {
      return (array) $result->response->docs[0];
    }
    else {
      throw new Exception("Found too many solr items for id $id", 1);
    }
  }

  function convert_ss_names_to_solr($asset) {
    $fields = $this->ini['fields'];
    $solr_asset = array();
    foreach ($asset as $k => $v) {
      if (empty($fields["$k"])) {
        throw new Exception("Missing solr field name for $k", 1);
      }
      $sk = $fields["$k"];
      $solr_asset["$sk"] = $asset["$k"];
    }
    return $solr_asset;
  }

}
