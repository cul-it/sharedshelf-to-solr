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
      throw new Exception("3 Error Processing Request: " . $url, 1);
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
      throw new Exception("4 Error Processing Request: " . $url, 1);
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
    return $json;
  }

  function format_add_asset_field_values($asset) {
    $data2 = array('add' => array('commitWithin' => 1000, 'doc' => $asset, ),);
    return json_encode($data2);
  }

/**
 * the place to handle fields that we want to add to solr, that are not in the original sharedshelf
 * field set.
 * @param array &$asset data values loaded in from sharedshelf, keyed by the names on the
 * right hand side of field[] declarations in the .ini files, for example
 * ; Country
 * fields[fd_1979_multi_s] = "country_location_tesim"
 * the value in $asset would be
 * $asset['country_location_tesim'] == the sharedshelf value for fd_1979_multi_s
 *
 * copy fields
 * copy_field[another_country] = "country_location_tesim"
 * would copy the solr value from $asset['country_location_tesim'] into $asset['another_country'] for the
 * current record
 *
 * set fields
 * set_solr_field[my_country] = "tis of Thee"
 * would set the value of $asset['my_country'] to the string "tis of Thee"
 * for all records
 *
 * other ones
 * set_location and set_geojson set up really custom values involving multiple $asset fields
 * and store them in new $asset fields
 */
  function add_custom_fields(&$asset) {
    if (isset($this->ini['copy_field'])) {
      foreach($this->ini['copy_field'] as $ss_solr_key => $solr_key) {
        /* copy_field - dupicate of the values stored under
        */
        if (!isset($asset["$ss_solr_key"])) {
          $asset["$solr_key"] = "ERROR: copy_field missing: $solr_key <- $ss_solr_key";
        }
        else {
          $asset["$solr_key"] = $asset["$ss_solr_key"];
        }
      }
    }
    if (isset($this->ini['set_solr_field'])) {
      foreach($this->ini['set_solr_field'] as $solr_key => $value) {
        $asset["$solr_key"] = $value;
      }
    }
    if (isset($this->ini['set_location'])) {
      foreach($this->ini['set_location'] as $solr_key => $value) {
        // grab solr field names for lat and lon
        list($lat,$lon) = explode(',', $value);
        if (isset($asset["$lat"]) && isset($asset["$lon"])) {
          // set the value of the field to the two field values separated by a comma
          $value = $asset["$lat"] . ',' . $asset["$lon"];
          $asset["$solr_key"] = $value;
        }
      }
    if (isset($this->ini['set_geojson'])) {
      foreach($this->ini['set_geojson'] as $solr_key => $value) {
        // grab solr field names for lat and lon
        list($lat,$lon,$loc,$id,$thumb) = explode(',', $value);
        if (isset($asset["$lat"]) && isset($asset["$lon"]) && isset($asset["$loc"]) && isset($asset["$thumb"])) {
          // set the value of the field to the two field values separated by a comma
          $value = '{"type":"Feature","geometry":{"type":"Point","coordinates":[' . $asset["$lon"] . ',' . $asset["$lat"] . ']},"properties":{"placename":"' . $asset["$loc"] . '","id":"' . $asset["$id"] . '","thumb":"' .$asset["$thumb"] . '"}}';
          $asset["$solr_key"] = $value;
        }
      }
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
    // print_r($json);
    // die('here');
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("5 Error Processing Request: $err", 1);
    }
    return $status;
  }

  function add($assets) {
    // $assets have names converted to solr already
    $json = '';
    foreach ($assets as $asset) {
      debug($asset, 'before add_custom_fields', false);
      $this->add_custom_fields($asset);
      debug($asset, 'after add_custom_fields', true);
      $json .= $this->format_add_asset_field_values($asset);
    }
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("Error Processing add Request: $err", 1);
    }
    return $status;
  }

  function get_item($id) {
    // return array of field values for given solr item
    // note: colon characters in $id must be escaped
    $id = str_replace(':', '\:', $id);
    $q = "q=id:$id&wt=json";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $found = isset($result->response->numFound) ? $result->response->numFound : 0;
    if ($found == 0) {
      return array();
    }
    elseif ($found == 1) {
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
      if (!empty($fields["$k"])) {
        $sk = $fields["$k"];
        $solr_asset["$sk"] = $asset["$k"];
      }
    }
    return $solr_asset;
  }

  function delete_items($ids = array()) {
    $ids_escaped = array();
     foreach ($ids as $id) {
      // note: colon characters in $id must be escaped
      $ids_escaped[] = str_replace(':', '\:', $id);
    }
    $deletes = implode(' OR ', $ids_escaped);
    $cmd = array('delete' => array('query' => "id:($deletes)", 'commitWithin' => 500));
    $json = json_encode($cmd);
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("6 Error Processing Delete Request: $err", 1);
    }
    return $status;
  }

  function get_all_ids($project_id) {
    $max_to_find = 99999;
    $q = "q=project_id_ssi:$project_id&wt=json&start=0&rows=$max_to_find&fl=id";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $found = isset($result->response->numFound) ? $result->response->numFound : 0;
    if ($found >= $max_to_find) {
      throw new Exception("get_all_ids need to increase maximum number of ids to find", 1);
    }
    elseif ($found > 0) {
      $ids = array();
      foreach ($result->response->docs as $doc) {
        $ids[] = $doc->id;
      }
      return $ids;
    }
    else {
      return array();
    }
  }

}
