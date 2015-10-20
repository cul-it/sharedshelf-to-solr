<?php
class SharedShelfService {

  private $sharedshelf_user = '';
  private $sharedshelf_password = '';
  private $cookie_jar_path = '';
  private $sharedshelf_url = 'http://catalog.sharedshelf.artstor.org';

  function __construct($user, $password, $cookiejar = '/tmp/SharedShelfService_cookies.txt') {
    $this->cookie_jar_path = $cookiejar;
    if (!file_exists($this->cookie_jar_path)) {
      if (touch($this->cookie_jar_path) === FALSE) {
        throw new Exception("Cookie jar file must exist: " . $this->cookie_jar_path, 1);
      }
    }
    $this->sharedshelf_user = $user;
    $this->sharedshelf_password = $password;
    $this->login();
  }

  private function get_query_params($params) {
    $query = '';
    //create name value pairs seperated by &
    foreach($params as $k => $v) {
      $query .= $k . '='. urlencode($v) .'&';
    }
    rtrim($query, '&');
    return $query;
  }

  private function get_cookies($params) {
    $url = $this->sharedshelf_url . '/account';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar_path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // get headers too with this line
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $result = curl_exec($ch);
    curl_close($ch);
    // get cookie
    // multi-cookie variant contributed by @Combuster in comments
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
    $cookies = array();
    foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
    }
    if (!isset($cookies['sharedshelf'])) {
      throw new Exception("No sharedshelf cookie", 1);
    }
  }

  function get_response($url_suffix = '/account', $check_success = TRUE) {
    $url = $this->sharedshelf_url . $url_suffix;
    $ch = curl_init($url);
    if ($ch === FALSE) {
      curl_close($ch);
      throw new Exception("Bad get_response url: $url", 1);
    }
    $output = curl_exec ($ch);
    curl_close($ch);
    if ($output === FALSE) {
      throw new Exception("1 Error Processing Request: " . $url, 1);
    }
    $output = json_decode($output, true);
    if ($check_success) {
      if (!(isset($output['success']) && ($output['success'] === TRUE))) {
        throw new Exception("Error Processing Request - no success: " . $url, 1);
      }
    }
    return $output;
  }

  private function get_url($url_suffix = '/account', $require_extension = TRUE) {
    // sometimes the first time gets the url without an extension
    $url = $this->sharedshelf_url . $url_suffix;
    $ch = curl_init($url);
    if ($ch === FALSE) {
      curl_close($ch);
      throw new Exception("Bad request url: $url", 1);
    }
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar_path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
    $response = curl_exec($ch);
    preg_match_all('/^Location:(.*)$/mi', $response, $matches);
    $url2 = !empty($matches[1]) ? trim($matches[1][0]) : 'No redirect found';
    curl_close($ch);

    $extension = pathinfo($url2, PATHINFO_EXTENSION);
    if (!empty($extension) || $require_extension === FALSE) {
      return $url2;
    }

    // $url2 comes back without an extension (eg. .jpg) so get second redirect
    $ch = curl_init($url2);
    if ($ch === FALSE) {
      curl_close($ch);
      throw new Exception("Bad second request url: $url2", 1);
    }
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar_path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
    $response = curl_exec($ch);
    preg_match_all('/^Location:(.*)$/mi', $response, $matches);
    if (empty($matches[1])) {
      throw new Exception("Can't find url for $url", 1);
    }

    $url_out = trim($matches[1][0]);
    return $url_out;
  }

  function login() {
    $form_fields = array(
      'email' => $this->sharedshelf_user,
      'password' => $this->sharedshelf_password,
      );
    $params = $this->get_query_params($form_fields);
    $this->get_cookies($params);
  }

  function logged_in() {
    $response = $this->get_response('/account', FALSE);
    return (isset($response['logged_in']) && ($response['logged_in'] === TRUE));
  }

  function projects() {
    $response = $this->get_response('/projects');
    return $response;
  }

  function project_asset_list($project_id, $per_page = 100) {
    // simplest version
    $args = "start=0&limit=1&with_meta=false&sort=id&dir=ASC";
    $assets = $this->get_response("/projects/$project_id/assets?$args");
    if (!isset($assets['total'])) {
      throw new Exception("Invalid project: $project_id", 1);
    }
    $total = $assets['total'];
    $ids = array();
    for ($start = 0; $start < $total; $start += $per_page) {
      $args = "start=$start&limit=$per_page&with_meta=false&sort=id&sort=id&dir=ASC";
      $assets = $this->get_response("/projects/$project_id/assets?$args");
      foreach($assets['assets'] as $asset) {
        $ids[] = $asset['id'];
      }
    }
    $ids = array_unique($ids, SORT_NUMERIC);
    if (count($ids) != $total) {
      throw new Exception("SS did not return enough unique IDs: expected $total; counted " . count($ids), 1);
    }
    return $ids;
  }

  function project_asset_ids($project_id, $start_date = FALSE) {
    // return array of all asset ids in this project
    $assets = $this->get_response("/projects/$project_id/assets");
    if (!isset($assets['total'])) {
      throw new Exception("Invalid project: $project_id", 1);
    }
    // 2014-10-08T14:39:58+00:00
    $date_format = 'Y-m-d\TH:i:sP';
    if ($start_date !== FALSE) {
      // 2014-10-08T14:39:58+00:00
      $timezone = new DateTimeZone('America/New_York');
      $start_datetime = DateTime::createFromFormat($date_format, $start_date, $timezone);
      if ($start_datetime === FALSE) {
        throw new Exception("Invalid date format: $start_date", 1);
      }
    }
    $total = $assets['total'];
    $asset_ids = array();
    $per_page = 25;
    for ($item = 0; $item < $total; $item += $per_page) {
      $args = "start=$item&limit=$per_page&with_meta=false";
      $assets = $this->get_response("/projects/$project_id/assets?$args");
      foreach($assets['assets'] as $asset) {
        if ($start_date !== FALSE) {
          if (!isset($asset['updated_on'])) {
            $id = $asset['id'];
            throw new Exception("Missing expected updated_on field: $id", 1);
          }
          $asset_date = DateTime::createFromFormat($date_format, $asset['updated_on'], $timezone);
          if ($asset_date === FALSE) {
            throw new Exception("Invalid asset date format: $asset_date", 1);
          }
          if ($asset_date >= $start_datetime) {
            $asset_ids[] = $asset['id'];
          }
        }
        else {
          $asset_ids[] = $asset['id'];
        }
      }
    }
    return $asset_ids;
  }

  function assets_modified_since_request($project_id, $start_date = "07/01/2011") {
    // return a list of asset ids for items modified on or since the date
    $total = $this->project_assets_count($project_id);
    $per_page = 25;
    $asset_ids = array();
    $filter = array(
      'type' => 'date',
      'comparison' => 'ge',
      'value' => $start_date,
      'field' => 'updated_on',
      );
    $filter_text = json_encode( (object) $filter);
    $suffix = "limit=$per_page&with_meta=false&filter=$filter_text";
    for ($item = 0; $item < $total; $item += $per_page) {
      $args = "start=$item&$suffix";
      $assets = $this->get_response("/projects/$project_id/assets?$args");
      foreach($assets['assets'] as $asset) {
        $asset_ids[] = $asset['id'];
      }
      if ($item == 0) {
        // reset total to total with filter
        if (isset($assets['assets']['total'])) {
          $total = $assets['assets']['total'];
        }
        else {
          throw new Exception("Can't find filtered asset total in assets_modified_since", 1);
        }
      }
    }
    return $asset_ids;
  }

  function project_assets_count($project_id) {
    $args = "start=0&limit=1";
    $assets = $this->get_response("/projects/$project_id/assets?$args");
    if (!isset($assets['total'])) {
      throw new Exception("Can't find assets total for project: $project_id", 1);
    }
    $total = $assets['total'];
    return $total;
  }

  function project_assets($project_id, $start, $count) {
    $args = "start=$start&limit=$count&with_meta=false";
    $assets = $this->get_response("/projects/$project_id/assets?$args");
    if (!isset($assets['assets'])) {
      throw new Exception("Error Processing project_assets: $project, $start, $count", 1);
    }
    return $assets['assets'];
  }

  function project_metadata($project_id) {
    // return metadata section of asset array
    $args = "start=0&limit=1&with_meta=true";
    $url = "/projects/$project_id/assets?$args";
    $assets = $this->get_response($url);
    if (!isset($assets['metaData'])) {
      throw new Exception("No metadata", 1);
    }
    return $assets['metaData'];
  }

  function project_fields($project_id) {
    // some of the fields have descriptions
    $metadata = $this->project_metadata($project_id);
    $columns = $metadata['columns'];
    $fields = array();
    foreach($columns as $column) {
      $name = $column['dataIndex'];
      $description = $column['header'];
      $fields["$name"] = $description;
    }

    // other fields do not
    $total = $this->project_assets_count($project_id);
    $per_page = 100;
    for ($start = 0; $start < $total; $start += $per_page) {
      $assets = $this->project_assets($project_id, $start, $per_page);
      foreach ($assets as $asset) {
        foreach ($asset as $k => $v) {
          if (!isset($fields["$k"])) {
            if (is_scalar($k)) {
              $fields["$k"] = '???';
            }
            else {
              var_dump($asset);
              echo "nonscalar: ";
              var_dump($k);
              die('here');
            }
          }
        }
      }
    }

    if (ksort($fields) === FALSE) {
      throw new Exception("can't sort fields for project $project_id", 1);
    }
    return $fields;
  }

  function project_fields_ini($project_id) {
    $fields = $this->project_fields($project_id);
    $ini_text = "\n; *********Fields to include in .ini file:\n";
    $ini_text .= "; ; SharedsShelf field name description\n";
    $ini_text .= "; fields[sharedshelf_field_name] = \"solr_field_name\"\n";
    foreach( $fields as $ss_field => $desc) {
      $matches = null;
      $returnValue = preg_match('/_[ist]$/', $ss_field, $matches);
      $solr_field = ($returnValue == 1) ? $ss_field : "${ss_field}_s";
      if ($solr_field == "id_s") $solr_field = "id";  // special case for id field!
      $ini_text .= "\n; $desc\n";
      $ini_text .= "fields[$ss_field] = \"$solr_field\"\n";
    }
    return $ini_text;
  }

  function asset($asset_id) {
    // return all metadata about the asset
    $asset = $this->get_response("/assets/$asset_id");
    if (!(isset($asset['asset'][0]))) {
      throw new Exception("2 Error Processing Request: asset id $asset_id", 1);
    }
    return $asset['asset'][0];
  }

  function asset_field_values($asset) {
    // flatten out the array field values in an asset
    $flat = array();
    foreach ($asset as $k => $v) {
      if (is_array($v)) {
        $matches = null;
        $returnValue = preg_match('/_lookup$/', $k, $matches);
        if ($returnValue == 1) {
          if (isset($v['display_value'])) {
            $flat["$k"] = $v['display_value'];
          }
          else {
            throw new Exception("Missing display_value: $k " . print_r($v, TRUE), 1);
          }
        }
        else {
          $children = FALSE;
          foreach ($v as $v_child) {
            if (is_array($v_child)) {
              $children = TRUE;
              break;
            }
          }
          if ($children) {
            $mess = array();
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($v));
            foreach($it as $key => $child) {
              $mess[] = "$key | $child";
            }
            $flat["$k"] = implode("; ", $mess);
          }
          else {
            $flat["$k"] = implode("; ", $v);
          }
        }
      }
      else {
        $flat["$k"] = $v;
      }
    }
    return $flat;
  }
  /**
   * track down the url for this asset's sharedshelf image
   * @param  integer $asset_id asset id
   * @return string           url for main image of this asset
   */
  function media_url($asset_id) {
    if (empty($asset_id)) {
      throw new Exception("Error Processing media_url Request", 1);
    }
    $url = $this->get_url("/assets/$asset_id/representation");
    $file_headers = @get_headers($url);
    if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
      throw new Exception("URL Not Found: $url", 1);
      }
    return $url;
  }

 function media_derivative_url($asset_id, $size) {
    if (empty($asset_id)) {
      throw new Exception("Error Processing media_derivative_url Request", 1);
    }
    // note: the urls for derivatives do not include an extension!! -> FALSE arg to get_url
    $url = $this->get_url("/assets/$asset_id/representation/size/$size", FALSE);
    $file_headers = @get_headers($url);
    if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
      throw new Exception("URL Not Found: $url", 1);
      }
    return $url;
  }

  function media_dimensions($asset_id) {
    if (empty($asset_id)) {
      throw new Exception("Error Processing media_dimensions Request", 1);
    }
    $details = $this->get_response("/assets/$asset_id/representation/details");
    if (isset($details['width']) && isset($details['height'])) {
      $output = array('width' => $details['width'], 'height' => $details['height']);
    }
    else {
      $output = FALSE;
    }
    return $output;
  }

}
