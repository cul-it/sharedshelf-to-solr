<?php
class eCommonsService {

    private $ecommons_api = 'https://ecommons.cornell.edu/rest';
    private $ecommons_user = '';
    private $ecommons_password = '';
    private $cookie_jar_path = '';
  
    function __construct($user, $password, $cookiejar = '/tmp/SharedShelfService_cookies.txt') {
        $this->cookie_jar_path = $cookiejar;
        if (!file_exists($this->cookie_jar_path)) {
          if (touch($this->cookie_jar_path) === FALSE) {
            throw new Exception("Cookie jar file must exist: " . $this->cookie_jar_path, 1);
          }
        }
        $this->ecommons_user = $user;
        $this->ecommons_password = $password;
        $this->login();
      }
        
    function get_response($url_suffix = '/communities', $check_success = TRUE) {
        $url = $this->ecommons_api . $url_suffix;
        $ch = curl_init($url);
        if ($ch === FALSE) {
          curl_close($ch);
          throw new Exception("Bad get_response url: $url", 1);
        }
        $options = array(
          CURLOPT_CONNECTTIMEOUT => 120,
          CURLOPT_COOKIEFILE => $this->cookie_jar_path, /* make sure you provide FULL PATH to cookie files*/
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 120,
          );
        $allswell = curl_setopt_array($ch, $options);
        if ($allswell === FALSE) {
          throw new Exception("get_response failed to set curl options", 1);
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

    function login() {
        $form_fields = array(
          'email' => $this->sharedshelf_user,
          'password' => $this->sharedshelf_password,
          );
        $params = $this->get_query_params($form_fields);
        $this->get_cookies($params);
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
        $url = $this->ecommons_api . '/login';
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
            
}
  