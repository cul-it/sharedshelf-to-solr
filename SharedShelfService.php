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

  function project_assets($project_id) {
    // return array of all asset ids in this project
    $assets = $this->get_response("/projects/$project_id/assets");
    if (!isset($assets['total'])) {
      throw new Exception("Invalid project: $project_id", 1);
    }
    $total = $assets['total'];
    $asset_ids = array();
    $per_page = 25;
    for ($item = 0; $item < $total; $item += $per_page) {
      $args = "start=$item&limit=$per_page";
      $assets = $this->get_response("/projects/$project_id/assets?$args");
      foreach($assets['assets'] as $asset) {
        $asset_ids[] = $asset['id'];
      }
    }
    return $asset_ids;
  }

  function project_metadata($project_id) {
    // return metadata section of asset array
    $args = "start=1&limit=1&with_meta=true";
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

  function asset($asset_id) {
    // return all metadata about the asset
    $asset = $this->get_response("/assets/$asset_id");
    return $asset;
  }

  function asset_field_values($asset) {
    // flatten out the array field values in an asset
    $flat = array();
    return $flat;
  }

}
