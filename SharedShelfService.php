<?php

class ForumRequestException extends Exception
{
}

class SharedShelfService
{
    private $sharedshelf_user = '';
    private $sharedshelf_password = '';
    private $cookie_jar_path = '';
    private $sharedshelf_url = 'https://forum.jstor.org';

    public function __construct($user, $password, $cookiejar = '/tmp/SharedShelfService_cookies.txt')
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'SSCookies.txt');
        $this->cookie_jar_path = $temp_file;
        if (!file_exists($this->cookie_jar_path)) {
            if (false === touch($this->cookie_jar_path)) {
                throw new Exception('Cookie jar file must exist: '.$this->cookie_jar_path, 1);
            }
        }
        $this->sharedshelf_user = $user;
        $this->sharedshelf_password = $password;
        $this->login();
    }

    private function get_query_params($params)
    {
        $query = '';
        //create name value pairs seperated by &
        foreach ($params as $k => $v) {
            $query .= $k.'='.urlencode($v).'&';
        }
        rtrim($query, '&');

        return $query;
    }

    private function get_cookies($params)
    {
        $url = $this->sharedshelf_url.'/account';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar_path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // get headers too with this line
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $count = is_array($params) ? count($params) : 0;
        curl_setopt($ch, CURLOPT_POST, $count);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        // get cookie
        // multi-cookie variant contributed by @Combuster in comments
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        if (!isset($cookies['sharedshelf'])) {
            throw new Exception('No sharedshelf cookie', 1);
        }
    }

    public function get_response($url_suffix = '/account', $check_success = true)
    {
        $url = $this->sharedshelf_url.$url_suffix;
        $ch = curl_init($url);
        if (false === $ch) {
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
        if (false === $allswell) {
            throw new Exception('get_response failed to set curl options', 1);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        if (false === $output) {
            throw new Exception('1 Error Processing Request: '.$url, 1);
        }
        $output = json_decode($output, true);
        if ($check_success) {
            if (!(isset($output['success']) && (true === $output['success']))) {
                throw new ForumRequestException('Error Processing Request - no success: '.$url, 1);
            }
        }

        return $output;
    }

    private function get_url($url_suffix = '/account', $require_extension = true)
    {
        // sometimes the first time gets the url without an extension
        $url_list = array();
        $url_list[] = $this->sharedshelf_url.$url_suffix;

        $ch = curl_init();
        if (false === $ch) {
            curl_close($ch);
            throw new Exception("Bad request url in get_url: $url", 1);
        }

        for ($redirects = 0; $this->follow_redirects($ch, $url_list); ++$redirects);
        $url = end($url_list);

        curl_close($ch);

        if ($require_extension) {
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            if (empty($extension)) {
                throw new Exception("Missing required extension: $url", 1);
            }
        }

        return $url;
    }

    /**
     * take the last url in the array, if it redirects, add new url to array.
     *
     * @param array $url_array urls
     *
     * @return bool return TRUE if last url redirects
     */
    private function follow_redirects(&$ch, &$url_array)
    {
        if (!is_array($url_array)) {
            throw new Exception('follow_redirects array required', 1);
        }
        $last_url = end($url_array);
        if (false === $last_url) {
            throw new Exception('follow_redirects empty array', 1);
        }

        $options = array(
      CURLOPT_URL => $last_url,
      CURLOPT_CONNECTTIMEOUT => 120,
      CURLOPT_COOKIEFILE => $this->cookie_jar_path, /* make sure you provide FULL PATH to cookie files*/
      CURLOPT_FOLLOWLOCATION => false,  // We want to just get redirect url but not to follow it.
      CURLOPT_HEADER => true,    // We'll parse redirect url from header.
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 120,
      CURLOPT_NOBODY => true, //exclude the body from the output
      );
        $allswell = curl_setopt_array($ch, $options);
        if (false === $allswell) {
            throw new Exception('follow_redirects failed to set curl options', 1);
        }
        $response = curl_exec($ch);

        preg_match_all('/^Location:(.*)$/mi', $response, $matches);
        if (empty($matches[1])) {
            $redirected = false;
        } else {
            $redirected = true;
            $url_array[] = trim($matches[1][0]); // append the url redirected to to the array
        }

        return $redirected;
    }

    public function login()
    {
        $form_fields = array(
      'email' => $this->sharedshelf_user,
      'password' => $this->sharedshelf_password,
      );
        $params = $this->get_query_params($form_fields);
        $this->get_cookies($params);
    }

    public function logged_in()
    {
        $response = $this->get_response('/account', false);

        return isset($response['logged_in']) && (true === $response['logged_in']);
    }

    public function projects()
    {
        $response = $this->get_response('/projects');

        return $response;
    }

    public function project_asset_count($project_id)
    {
        $args = 'start=0&limit=1&with_meta=false&sort=id&dir=ASC';
        $assets = $this->get_response("/projects/$project_id/assets?$args");
        if (!isset($assets['total'])) {
            throw new Exception("Invalid project: $project_id", 1);
        }
        $total = $assets['total'];

        return $total;
    }

    public function project_asset_list($project_id, $per_page = 100)
    {
        // simplest version
        $args = 'start=0&limit=1&with_meta=false&sort=id&dir=ASC';
        $assets = $this->get_response("/projects/$project_id/assets?$args");
        if (!isset($assets['total'])) {
            throw new Exception("Invalid project: $project_id", 1);
        }
        $total = $assets['total'];
        $ids = array();
        for ($start = 0; $start < $total; $start += $per_page) {
            $args = "start=$start&limit=$per_page&with_meta=false&sort=id&sort=id&dir=ASC";
            $assets = $this->get_response("/projects/$project_id/assets?$args");
            foreach ($assets['assets'] as $asset) {
                $ids[] = $asset['id'];
            }
        }
        $ids = array_unique($ids, SORT_NUMERIC);
        if (count($ids) != $total) {
            throw new Exception("SS did not return enough unique IDs: expected $total; counted ".count($ids), 1);
        }

        return $ids;
    }

    /**
     * return associative array with id as the key and a given field as the value.
     *
     * @param int $project_id ss project number
     * @param  text field name of ss field to return
     * @param int $per_page count of items to collect from ss at once
     *
     * @return array [description]
     *
     * query parameter to get just the published ones
     * [{"type":"string","value":"1114","field":"publishing_status"}]
     */
    public function project_asset_list_values($project_id, $field_name, $per_page = 100)
    {
        // simplest version
        $args = 'start=0&limit=1&with_meta=false&sort=id&dir=ASC';
        $assets = $this->get_response("/projects/$project_id/assets?$args");
        if (!isset($assets['total'])) {
            throw new Exception("Invalid project: $project_id", 1);
        }
        $total = $assets['total'];
        $ids = array();
        // $publish_counts = array('no status' => 0, 'Published' => 0, 'Suppressed' => 0);
        for ($start = 0; $start < $total; $start += $per_page) {
            $args = "start=$start&limit=$per_page&with_meta=false&sort=id&sort=id&dir=ASC";
            $assets = $this->get_response("/projects/$project_id/assets?$args");
            foreach ($assets['assets'] as $asset) {
                $id = $asset['id'];
                $ids["$id"] = $asset["$field_name"];
            }
        }
        // print_r($publish_counts);
        if (count($ids) != $total) {
            throw new Exception("SS did not return enough unique IDs: expected $total; counted ".count($ids), 1);
        }
        if (false === ksort($ids, SORT_NUMERIC)) {
            throw new Exception('Unable to ksort project_asset_list_values', 1);
        }

        return $ids;
    }

    public function project_asset_ids($project_id, $start_date = false)
    {
        // return array of all asset ids in this project
        $assets = $this->get_response("/projects/$project_id/assets");
        if (!isset($assets['total'])) {
            throw new Exception("Invalid project: $project_id", 1);
        }
        // 2014-10-08T14:39:58+00:00
        $date_format = 'Y-m-d\TH:i:sP';
        if (false !== $start_date) {
            // 2014-10-08T14:39:58+00:00
            $timezone = new DateTimeZone('America/New_York');
            $start_datetime = DateTime::createFromFormat($date_format, $start_date, $timezone);
            if (false === $start_datetime) {
                throw new Exception("Invalid date format: $start_date", 1);
            }
        }
        $total = $assets['total'];
        $asset_ids = array();
        $per_page = 25;
        for ($item = 0; $item < $total; $item += $per_page) {
            $args = "start=$item&limit=$per_page&with_meta=false";
            $assets = $this->get_response("/projects/$project_id/assets?$args");
            foreach ($assets['assets'] as $asset) {
                if (false !== $start_date) {
                    if (!isset($asset['updated_on'])) {
                        $id = $asset['id'];
                        throw new Exception("Missing expected updated_on field: $id", 1);
                    }
                    $asset_date = DateTime::createFromFormat($date_format, $asset['updated_on'], $timezone);
                    if (false === $asset_date) {
                        throw new Exception("Invalid asset date format: $asset_date", 1);
                    }
                    if ($asset_date >= $start_datetime) {
                        $asset_ids[] = $asset['id'];
                    }
                } else {
                    $asset_ids[] = $asset['id'];
                }
            }
        }

        return $asset_ids;
    }

    public function assets_modified_since_request($project_id, $start_date = '07/01/2011')
    {
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
        $filter_text = json_encode((object) $filter);
        $suffix = "limit=$per_page&with_meta=false&filter=$filter_text";
        for ($item = 0; $item < $total; $item += $per_page) {
            $args = "start=$item&$suffix";
            $assets = $this->get_response("/projects/$project_id/assets?$args");
            foreach ($assets['assets'] as $asset) {
                $asset_ids[] = $asset['id'];
            }
            if (0 == $item) {
                // reset total to total with filter
                if (isset($assets['assets']['total'])) {
                    $total = $assets['assets']['total'];
                } else {
                    throw new Exception("Can't find filtered asset total in assets_modified_since", 1);
                }
            }
        }

        return $asset_ids;
    }

    public function project_assets_count($project_id)
    {
        $args = 'start=0&limit=1';
        $assets = $this->get_response("/projects/$project_id/assets?$args");
        if (!isset($assets['total'])) {
            throw new Exception("Can't find assets total for project: $project_id", 1);
        }
        $total = $assets['total'];

        return $total;
    }

    public function project_assets($project_id, $start, $count)
    {
        $args = "start=$start&limit=$count&with_meta=false";
        $assets = $this->get_response("/projects/$project_id/assets?$args");
        if (!isset($assets['assets'])) {
            throw new Exception("Error Processing project_assets: $project, $start, $count", 1);
        }

        return $assets['assets'];
    }

    public function project_metadata($project_id)
    {
        // return metadata section of asset array
        $args = 'start=0&limit=1&with_meta=true';
        $url = "/projects/$project_id/assets?$args";
        $assets = $this->get_response($url);
        if (!isset($assets['metaData'])) {
            throw new Exception('No metadata', 1);
        }

        return $assets['metaData'];
    }

    public function project_fields($project_id)
    {
        // some of the fields have descriptions
        $metadata = $this->project_metadata($project_id);
        $columns = $metadata['columns'];
        $fields = array();
        foreach ($columns as $column) {
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
                        } else {
                            var_dump($asset);
                            echo 'nonscalar: ';
                            var_dump($k);
                            die('here');
                        }
                    }
                }
            }
        }

        if (false === ksort($fields)) {
            throw new Exception("can't sort fields for project $project_id", 1);
        }

        return $fields;
    }

    public function project_fields_ini($project_id)
    {
        $fields = $this->project_fields($project_id);
        $ini_text = "\n; *********Fields to include in .ini file:\n";
        $ini_text .= "; ; SharedsShelf field name description\n";
        $ini_text .= "; fields[sharedshelf_field_name] = \"solr_field_name\"\n";
        foreach ($fields as $ss_field => $desc) {
            $matches = null;
            $returnValue = preg_match('/_[ist]$/', $ss_field, $matches);
            $solr_field = (1 == $returnValue) ? $ss_field : "${ss_field}_s";
            if ('id_s' == $solr_field) {
                $solr_field = 'id';
            }  // special case for id field!
            $ini_text .= "\n; $desc\n";
            $ini_text .= "fields[$ss_field] = \"$solr_field\"\n";
        }

        return $ini_text;
    }

    public function asset($asset_id)
    {
        // return all metadata about the asset
        $asset = $this->get_response("/assets/$asset_id");
        if (!(isset($asset['asset'][0]))) {
            throw new Exception("2 Error Processing Request: asset id $asset_id", 1);
        }

        return $asset['asset'][0];
    }

    public function asset_field_values($asset)
    {
        // flatten out the array field values in an asset
        // if a field is an array, stick all the array's elements in a single text string
        $flat = array();
        foreach ($asset as $k => $v) {
            if (is_array($v)) {
                $matches = null;
                $returnValue = preg_match('/_lookup$/', $k, $matches);
                if (1 == $returnValue) {
                    if (isset($v['display_value'])) {
                        $flat["$k"] = trim($v['display_value']);
                    }
                } else {
                    $children = false;
                    $trimmed = array();
                    foreach ($v as $v_child) {
                        if (is_array($v_child)) {
                            $children = true;
                            break;
                        } else {
                            // save non-array items trimmed
                            // will be incomplete if an arrray item is found
                            // but it will not be used!
                            $trimmed[] = trim($v_child);
                        }
                    }
                    if ($children) {
                        $mess = array();
                        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($v));
                        foreach ($it as $key => $child) {
                            $mess[] = "$key | ".trim("$child");
                        }
                        $flat["$k"] = implode('; ', $mess);
                    } else {
                        $flat["$k"] = implode('; ', $trimmed);
                    }
                }
            } else {
                $flat["$k"] = trim($v);
            }
        }

        return $flat;
    }

    /**
     * returns the related information for the asset. It is assumed that
     * the asset is published to SSC.
     *
     * @param int $asset_id asset id
     *
     * @return string iiif json url for main image of this asset
     */
    public function media_iiif_info($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing media_iiif_url Request', 1);
        }
        $details = $this->get_response("/iiifmap/ss/$asset_id", false);
        if (empty($details['info_url'])) {
            return array();  // empty
        }

        return $details;
    }

    /**
     * returns the URL for the iiif json url of the asset. It is assumed that
     * the asset is published to SSC.
     *
     * @param int $asset_id asset id
     *
     * @return string iiif json url for main image of this asset
     */
    public function media_iiif_url($asset_id)
    {
        try {
            $details = $this->media_iiif_info($asset_id);
            $url = $details['info_url'];
        } catch (Exception $e) {
            // tolerate missing sharedshelf iiif links
      $url = array();  // empty
        }

        return $url;
    }

    /**
     * track down the url for this asset's sharedshelf image.
     *
     * @param int $asset_id asset id
     *
     * @return string url for main image of this asset
     */
    public function media_url($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing media_url Request', 1);
        }
        // note: forum now needs dynamic redirects at run time
        $details = $this->get_response("/assets/$asset_id/representation/details");
        if (!isset($details['url'])) {
            // allow empty media url (For partial collections, etc.)
            //throw new Exception("media_url URL Not Found: $asset_id", 1);
            $url = '';
        }
        else {
            $url = $details['url'];
        }

        return $url;
    }

    public function media_file_extension($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing media_file_extension Request', 1);
        }
        $details = $this->get_response("/assets/$asset_id/representation/details");
        if (empty($details['name'])) {
            return '';
            //throw new Exception("Missing original filename in media_file_extension().", 1);
        }
        // filename of original file
        $filename = $details['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (empty($ext)) {
            throw new Exception("Empty filename extension: $filename", 1);
        }

        return $ext;
    }

    public function media_filename($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing media_file_extension Request', 1);
        }
        $details = $this->get_response("/assets/$asset_id/representation/details");
        if (empty($details['name'])) {
            return '';
            //throw new Exception("Missing original filename in media_file_extension().", 1);
        }
        // filename of original file
        $filename = $details['name'];
        $file = pathinfo($filename, PATHINFO_FILENAME);
        if (empty($file)) {
            throw new Exception("Empty filename: $filename", 1);
        }

        return $file;
    }

    public function media_derivative_url($media_url, $size)
    {
        if (empty($media_url)) {
            throw new Exception('Error Processing media_derivative_url Request', 1);
        }
        $size_name = '_size'.$size;
        $url = $media_url.$size_name;

        return $url;
    }

    public function media_dimensions($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing media_dimensions Request', 1);
        }
        $details = $this->get_response("/assets/$asset_id/representation/details");
        if (isset($details['width']) && isset($details['height'])) {
            $output = array('width' => $details['width'], 'height' => $details['height']);
            $output['all'] = $details;
        } else {
            $output = false;
        }

        return $output;
    }

    public function find_compound_objects($asset_id)
    {
        if (empty($asset_id)) {
            throw new Exception('Error Processing find_compound_objects Request', 1);
        }
        $details = $this->get_response("/assets/$asset_id/media-objects");
        print_r($details);
        $compound = [];
        if (!empty($details['items'])) {
            foreach ($details['items'] as $item) {
                $compound[] = $item['id'];
            }
        }
        foreach ($compound as $obj_id) {
            $obj = $this->get_response("/media-objects/$obj_id/representation/details");
            print_r([$obj_id . ' compound details', $obj]);
            die("here\n");
        }
        return $compound;
    }

    public function find_publishing_target_id($project_id, $publish_to = 'Shared Shelf Commons')
    {
        $meta = $this->project_metadata($project_id);
        if (is_array($meta['targets'])) {
            foreach ($meta['targets'] as $target) {
                if (0 == strcmp($target['target_name'], $publish_to)) {
                    return $target['id'];
                }
            }
        }

        return false;
    }
}
