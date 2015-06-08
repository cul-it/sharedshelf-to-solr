<?php
class SharedShelfService {

  private $cookie_jar_path = '/tmp/SharedShelfService_cookies.txt';
  private $sharedshelf_url = 'http://catalog.sharedshelf.artstor.org';

  function __construct() {
    if (!file_exists($this->cookie_jar_path)) {
      throw new Exception("Cookie jar file must exist: " . $this->cookie_jar_path, 1);
    }
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

  private function do_login($user, $password) {
    $form_fields = array(
      'email' => $user,
      'password' => $password,
      );
    $params = $this->get_query_params($form_fields);
    $this->get_cookies($params);
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

  function get_response($url_suffix = '/account') {
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
    if (isset($output['success']) && ($output['success'] === TRUE)) {
      return $output;
    }
    else {
      throw new Exception("Error Processing Request: " . $url, 1);
    }
  }

  function login() {
    $user = parse_ini_file('ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
    $this->do_login($user['email'], $user['password']);
  }
}
