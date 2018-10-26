<?php

class VersionConflictException extends Exception {}

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
    $count = is_array($params) ? count($params) : 0;
    curl_setopt($ch, CURLOPT_POST, $count);
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

  function remove_quotes_spaces($str) {
    //https://stackoverflow.com/questions/40724543/how-to-replace-decoded-non-breakable-space-nbsp
    $cleanup = array('"', ' ', "\xc2\xa0", "\t");
    $result = str_replace($cleanup, '', $str);
    return $result;
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
    $cleanup = array('"', ' '); // remove double quotes and blanks
    if (isset($this->ini['copy_field'])) {
      foreach($this->ini['copy_field'] as $ss_solr_key => $solr_key) {
        /* copy_field - dupicate of the values stored under
        */
        if (isset($asset["$ss_solr_key"])) {
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
          $value = str_replace($cleanup, '', $value);
          $asset["$solr_key"] = $value;
        }
      }
    }
    // solr needs author and title to be single valued
    // if an asset has more than one title/author
    // send only the first one to solr for the sort field
    if (isset($this->ini['set_single_value'])) {
        foreach($this->ini['set_single_value'] as $solr_key => $value) {
        // grab solr field names for lat and lon
        list($authors) = explode(',', $value);
        if (isset($asset["$authors"])) {
          // set the value of the field to the two field values separated by a comma
          if (is_array($asset["$authors"])) {
          $value = $asset["$authors"][0];}
          else {
            $value = $asset["$authors"];
          }
          $asset["$solr_key"] = $value;
        }
      }
    }

    if (isset($this->ini['set_geojson'])) {
      foreach($this->ini['set_geojson'] as $solr_key => $value) {
        // grab solr field names for lat and lon
        list($lat,$lon,$loc,$id,$thumb) = explode(',', $value);
        if (isset($asset["$lat"]) && isset($asset["$lon"]) && isset($asset["$loc"]) && isset($asset["$thumb"])) {
          // set the value of the field to the two field values separated by a comma
          $latlon = $asset["$lon"] . ',' . $asset["$lat"];
          $latlon = str_replace($cleanup, '', $latlon);
          $value = '{"type":"Feature","geometry":{"type":"Point","coordinates":[' . $latlon . ']},"properties":{"placename":"' . $asset["$loc"] . '","id":"' . $asset["$id"] . '","thumb":"' .$asset["$thumb"] . '"}}';
          $asset["$solr_key"] = $value;
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
      $this->add_custom_fields($asset);
      $json .= $this->format_add_asset_field_values($asset);
    }
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status == "409") {
      // version conflict detected (someone else changed solr record
      // while we were processing it)
      throw new VersionConflictException("Version conflict",1);
    }
    elseif ($status != "0") {
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

  function get_documents($start = 0, $max_to_find = 99999, $query_override = FALSE) {
    $query = ($query_override === FALSE) ? '*:*' : $query_override;
    $q = "q=$query&wt=json&start=$start&rows=$max_to_find";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $documents = array();
    if (!empty($result->response->docs)) {
      foreach ($result->response->docs as $doc) {
        // the docs come out as stdClass Object
        $documents[] = (array) $doc;
      }
    }
    return $documents;
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

  function delete_matching($query) {
    // query is like 'project_id_ssi:1234'
    $cmd = array('delete' => array('query' => $query, 'commitWithin' => 500));
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
  
  function get_count($query_override = FALSE) {
    $query = ($query_override === FALSE) ? '*:*' : $query_override;
    $q = "q=$query&wt=json&fl=id";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $found = isset($result->response->numFound) ? $result->response->numFound : 0;
    return $found;
  }

  function get_ids($start = 0, $max_to_find = 99999, $query_override = FALSE) {
    $query = ($query_override === FALSE) ? '*:*' : $query_override;
    $q = "q=$query&wt=json&start=$start&rows=$max_to_find&fl=id";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $ids = array();
    if (!empty($result->response->docs)) {
      foreach ($result->response->docs as $doc) {
        $ids[] = $doc->id;
        }
    }
    return $ids;
  }

  function get_all_ids($project_id, $max_to_find = 99999) {
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

  function get_all_ids_prefix($id_prefix, $start = 0, $max_to_find = 10) {
    $prefix = $id_prefix . '*';
    $q = "q=id:$prefix&wt=json&start=$start&rows=$max_to_find&fl=id";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $ids = array();
    if (!empty($result->response->docs)) {
      foreach ($result->response->docs as $doc) {
        $ids[] = $doc->id;
        }
    }
    return $ids;
   }

  function get_all_ids_prefix_type($id_prefix, $type = 'Book', $start = 0, $max_to_find = 10) {
    $prefix = $id_prefix . '*';
    $q = "q=id:$prefix human_readable_type_tesim:$type&wt=json&start=$start&rows=$max_to_find&fl=id";
    $json = $this->get('/select', $q);
    $result = json_decode($json);
    $ids = array();
    if (!empty($result->response->docs)) {
      foreach ($result->response->docs as $doc) {
        $ids[] = $doc->id;
        }
    }
    return $ids;
   }

  function add_without_custom($assets) {
    // $assets have names converted to solr already
    $json = '';
    foreach ($assets as $asset) {
      $json .= $this->format_add_asset_field_values($asset);
    }
    $json = $this->post_json('/update/json', $json);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status == "409") {
      // version conflict detected (someone else changed solr record
      // while we were processing it)
      throw new VersionConflictException("Version conflict",1);
    }
    elseif ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("7 Error Processing add_without_custom Request: $status", 1);
    }
    return $status;
  }

  function raw_get($url_suffix, $query) {
    return $this->get($url_suffix, $query);
  }

  /**
   * sets a commit message to solr
   * @return nothing
   */
  function commit() {
    $q = 'stream.body=%3Ccommit waitFlush="false"/%3E';
    $this->get('/update', $q);
  }

  /**
   * Re-loads schema.xml and solrconfig.xml
   * @return nothing
   */
  function reload($core = 'digitalcollections') {
    $q = 'action=RELOAD&core=' . $core;
    $this->get('/admin/cores', $q);
  }

  function extract($id, $url, $content_type = 'application/pdf') {
    // this replaces the solr document with contents from the extract
    $flds = array(
      'literal.id' => $id,
      'stream.url' => $url,
      'stream.contentType' => $content_type,
      'wt' => 'json',
      'fmap.content' => 'text_tsimv',
      'commit' => 'true',
      );
    $q = http_build_query($flds);
    $json = $this->get('/update/extract', $q);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("8 Error Processing extract Request: $status", 1);
    }
    return $status;
  }

  function extract_only($url, $content_type = 'application/pdf') {
    // this returns the content of the extracted document
    $flds = array(
      'extractOnly' => 'true',
      'extractFormat' => 'text',
      'stream.url' => $url,
      'stream.contentType' => $content_type,
      'wt' => 'json',
      );
    $q = http_build_query($flds);
    $json = $this->get('/update/extract', $q);
    $result = json_decode($json);
    $status = isset($result->responseHeader->status) ? $result->responseHeader->status : 1;
    if ($status != "0") {
      $err = print_r($result, TRUE);
      throw new Exception("9 Error Processing extract Request: $status", 1);
    }
    $text = $result->$url;
    //$metadata = $result["$url_metadata"]
    return $text;

  }

}
