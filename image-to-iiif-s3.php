<?php

// image-to-iiif-s3.php - convert an image to static iiif tiles and move tiles to Amazon s3

define('OUTPUT', true);

function get_url_redirected($url)
{
    // sometimes the first time gets the url without an extension
    $url_list = array();
    $url_list[] = $url;

    $ch = curl_init();
    if (false === $ch) {
        curl_close($ch);
        throw new Exception("Bad request url in get_url: $url", 1);
    }

    for ($redirects = 0; follow_redirects($ch, $url_list); ++$redirects) {}
    $url = end($url_list);

    curl_close($ch);

    return $url;
}

/**
 * take the last url in the array, if it redirects, add new url to array.
 *
 * @param array $url_array urls
 *
 * @return bool return TRUE if last url redirects
 */
function follow_redirects(&$ch, &$url_array)
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

function image_to_iiif_s3_mkdir($path, $group)
{
    // make a directory at the given path
    if (!is_dir($path)) {
        if (false === mkdir($path, 0775, true)) {
            throw new Exception("Can't create directory : $path", 1);
        }
        chgrp($path, $group);
        clearstatcache();
    }
}

function image_to_iiif_s3($image_url, $extension, $s3_path, $force_replacement = false, $save_tmp_files = false)
{
    // batch process information
    $task = parse_ini_file('sharedshelf-to-iiif-s3.ini', true);
    if (false === $task) {
        echo "Need sharedshelf-to-iiif-s3.ini\n";
        exit(1);
    }

    $s3_bucket = 's3://sharedshelftosolr.library.cornell.edu/public';
    $s3_url_prefix = 'https://s3.amazonaws.com/sharedshelftosolr.library.cornell.edu/public';
    $developer_group = 'lib_web_dev_role';

    if (OUTPUT) {
        echo "Checking pre-existing.\n";
    }

    // check if it already exists on S3
    $command = "/cul/share/miniconda/bin/s3cmd ls $s3_bucket/$s3_path";
    $output = '';
    $return_var = 0;
    $lastline = exec($command, $output, $return_var);
    if (0 != $return_var) {
        $output[] = 'Command failed: '.$command;
        $out = implode('PHP_EOL', $output);
        throw new Exception("Error Processing checking for iiif on s3: $out", 1);
    }
    if (false !== strpos($lastline, $s3_path)) {
        // assume this image has already been processed
        if (!$force_replacement) {
            // user did not want to replace any existing image
            return;
        }
    }
    if (OUTPUT) {
        echo "$command\n";
        echo implode("\n", $output)."\n";
    }

    if (OUTPUT) {
        echo "Create directories.\n";
    }

    // force temp dir group ownership
    $tmp_iiif = '/tmp/image-to-iiif-s3';
    image_to_iiif_s3_mkdir($tmp_iiif, $developer_group);
    chgrp($tmp_iiif, $developer_group);
    chmod($tmp_iiif, 02775); // setgid group sticky, all perms for owner and group, read and execute for others
    clearstatcache();

    // create temporary directories
    $temp_dir = "$tmp_iiif/$s3_path";
    image_to_iiif_s3_mkdir($temp_dir, $developer_group);
    $local_image = "$temp_dir/image.$extension";
    $local_iiif_dir = "$temp_dir/iiif";
    image_to_iiif_s3_mkdir($local_iiif_dir, $developer_group);

    if (OUTPUT) {
        echo "$temp_dir\n";
    }

    if (OUTPUT) {
        echo "Local copy of image.\n";
    }

    // make a local copy of the image
    $true_url = get_url_redirected($image_url);
    if (OUTPUT) {
        echo "original url: $image_url\n";
        echo "real url: $true_url\n";
    }
    $image = file_get_contents($true_url);
    if (false === $image || empty($image)) {
        throw new Exception("Can not load remote image $image_url");
    }
    if (OUTPUT) {
        $ilen = strlen($image);
        echo "image length: $ilen\n";
        echo "local image: $local_image\n";
    }
    if (false === file_put_contents($local_image, $image)) {
        throw new Exception("Can make local copy of image $image_url in $local_image");
    }
    if (OUTPUT) {
        if (false === file_exists($local_image)) {
            echo "Failed to make $local_image\n";
        }
    }

    $standard_format = 'jpg';
    if ($extension != $standard_format) {
        // convert copy of image to standard form with imagemagick mogrify
        if (OUTPUT) {
            echo "Converting image format to jpg.\n";
        }
        $command = "mogrify -format $standard_format $local_image";
        $output = '';
        $return_var = 0;
        $lastline = exec($command, $output, $return_var);
        if (0 != $return_var) {
            $output[] = 'Command failed: '.$command;
            $out = implode(PHP_EOL, $output);
            throw new Exception("Error Processing iiif: $out", 1);
        }
        $local_image = "$temp_dir/image.$standard_format";
    }

    if (OUTPUT) {
        echo "Making iiif tiles.\n";
    }

    // generate the static iiif tiles
    $script = $task['paths']['simeons_iiif_code'];
    $s3path = "$s3_url_prefix/$s3_path";
    //$command = "python $script -d $local_iiif_dir --tilesize 800 $local_image";
    //$command = "python $script -d $local_iiif_dir $local_image";
    $command = "/cul/share/miniconda/bin/python $script --api-version 2.0 --quiet --tilesize 512 -d $local_iiif_dir -p $s3path $local_image";
    $command .= ' --max-image-pixels=1000000000'; // see https://github.com/zimeon/iiif/issues/11
    $output = '';
    $return_var = 0;
    $lastline = exec($command, $output, $return_var);
    if (0 != $return_var) {
        $output[] = 'Command failed: '.$command;
        $out = implode(PHP_EOL, $output);
        throw new Exception("Error Processing iiif: $out", 1);
    }

    if (OUTPUT) {
        echo "Moving tiles to S3.\n";
    }

    // rsync tiles to s3
    $command = "/cul/share/miniconda/bin/s3cmd sync $local_iiif_dir/ $s3_bucket/$s3_path/";
    $output = '';
    $return_var = 0;
    $lastline = exec($command, $output, $return_var);
    if (0 != $return_var) {
        $output[] = 'Command failed: '.$command;
        $out = implode('PHP_EOL', $output);
        throw new Exception("Error Processing checking for iiif on s3: $out", 1);
    }

    // delete temp files
    if ($save_tmp_files) {
        if (OUTPUT) {
            echo "Saving $temp_dir\n";
        }
    } else {
        if (OUTPUT) {
            echo "Deleting $temp_dir\n";
        }
        system('/bin/rm -rf '.$temp_dir);
    }

    if (OUTPUT) {
        $s3path = "$s3_url_prefix/$s3_path/image/info.json";
        echo "Done: $s3path\n";
    }
}
