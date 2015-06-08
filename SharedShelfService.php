<?php
class SharedShelfService {

  private $cookie_jar_path = '/tmp/SharedShelfService_cookies.txt';
  private $sharedshelf_url = 'http://catalog.sharedshelf.artstor.org';

  function __construct() {
    if (!file_exists($this->$cookie_jar_path)) {
      throw new Exception("Cookie jar file must exist: " . $this->$cookie_jar_path, 1);
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
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->$cookie_jar_path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // get headers too with this line
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $result = curl_exec($ch);
    curl_close($ch);
  }

  function login() {
    $user = parse_ini_file('ssUser.ini');
    if ($user === FALSE) {
      throw new Exception("Need to create ssUser.ini. See README.md", 1);
    }
    do_login($user['email'], $user['password']);
  }
}
