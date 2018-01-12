<?php
class eCommonsService {

    private $ecommons_api = 'https://ecommons.cornell.edu/rest';
        
    function get_response($url_suffix = '/communities') {
        $url = $this->ecommons_api . $url_suffix;
        $ch = curl_init($url);
        if ($ch === FALSE) {
          curl_close($ch);
          throw new Exception("Bad get_response url: $url", 1);
        }
        $options = array(
          CURLOPT_CONNECTTIMEOUT => 120,
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
        return $output;
    }
     
}
  