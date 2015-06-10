<?php
class SharedShelfService {

  private $sharedshelf_user = '';
  private $sharedshelf_password = '';
  private $cookie_jar_path = '/tmp/SharedShelfService_cookies.txt';
  private $sharedshelf_url = 'http://catalog.sharedshelf.artstor.org';

  function __construct($user, $password) {
    if (!file_exists($this->cookie_jar_path)) {
      throw new Exception("Cookie jar file must exist: " . $this->cookie_jar_path, 1);
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
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar_path);
    /* make sure you provide FULL PATH to cookie files*/
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $output = curl_exec ($ch);
    curl_close($ch);
    if ($output === FALSE) {
      throw new Exception("Error Processing Request: " . $url, 1);
    }
    $output = json_decode($output, true);
    if ($check_success) {
      if (!(isset($output['success']) && ($output['success'] === TRUE))) {
        throw new Exception("Error Processing Request - no success: " . $url, 1);
      }
    }
    return $output;
  }

  function get_url($url_suffix = '/account') {
    $url = $this->sharedshelf_url . $url_suffix;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar_path);
    /* make sure you provide FULL PATH to cookie files*/
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $output = curl_exec ($ch);
    if ($output === FALSE) {
      curl_close($ch);
      throw new Exception("Error Processing Request: " . $url, 1);
    }
    $url_out = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
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
            $asset_ids[] = $asset['id'] . ':' . $asset_date->format('Y-m-d');
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
    $metadata = $this->project_metadata($project_id);
    $columns = $metadata['columns'];
    $fields = array();
    foreach($columns as $column) {
      $name = $column['dataIndex'];
      $description = $column['header'];
      $fields["$name"] = $description;
    }
    return $fields;
  }

  function project_fields_ini($project_id) {
    $fields = $this->project_fields($project_id);
    $ini_text = "\n; *********Fields to include in .ini file:\n";
    foreach( $fields as $ss_field => $desc) {
      $matches = null;
      $returnValue = preg_match('/_[is]$/', $ss_field, $matches);
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
      throw new Exception("Error Processing Request: asset id $asset_id", 1);
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
    return $this->get_url("/assets/$asset_id/representation");
  }

}