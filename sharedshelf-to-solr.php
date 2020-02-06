<?php

require __DIR__ . '/vendor/autoload.php';

use Ralouphie\Mimey;
use Smalot\Pdfparser;

// sharedshelf-to-solr - update all sharedshelf collections in solr

ini_set('memory_limit', '512M');

require_once 'SharedShelfService.php';
require_once 'SolrUpdater.php';
require_once 'SharedShelfToSolrLogger.php';
require_once 'image-to-iiif-s3.php';

class DatesMatchException extends Exception
{
}

define('TESTING_VERSION_CONFLICT', false);

function debug($item, $description = '', $die = true)
{
    if (!empty($description)) {
        echo PHP_EOL.'DEBUG: '.$description.PHP_EOL;
    }
    print_r($item);
    if ($die) {
        die('debugging'.PHP_EOL);
    }
}

function usage()
{
    global $argv;
    echo PHP_EOL;
    echo 'Usage: php '.$argv[0].' [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [--extract] [-p NNN] [-s NNN] [-n NNN]'.PHP_EOL;
    echo '--help - show this info'.PHP_EOL;
    echo '--force - ignore timestamps and rewrite all solr records'.PHP_EOL;
    echo '          note: if the collection .ini file has copy_pdf_to_s3, --force will also replace any .pdf files'.PHP_EOL;
    echo '--no-write - do everything EXCEPT writing the solr records'.PHP_EOL;
    echo '--use-dev-solr - override the solr core specified in .ini file using http://jrc88.solr.library.cornell.edu/solr/digitalcollections_dev'.PHP_EOL;
    echo '--skip - do not process this collection (only when -p is specified)'.PHP_EOL;
    echo '--extract - index the contents of any pdf files'.PHP_EOL;
    echo '-p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php'.PHP_EOL;
    echo '-s - start processing at the given SharedShelf asset number NNN (NNN must be numeric) (asset numbers ascend during processing)'.PHP_EOL;
    echo '-n - process only this many (integer) assets'.PHP_EOL;
    exit(0);
}

function split_delimited_fields(&$flattened_asset, $delimited_fields = array())
{
    foreach ($delimited_fields as $key => $delimiter) {
        if (!empty($flattened_asset["$key"])) {
            $value = $flattened_asset["$key"];
            if (false === strpos($value, $delimiter)) {
                $trimmed = trim($value);
                if (!empty($trimmed)) {
                    $flattened_asset["$key"] = $trimmed;
                }
            } else {
                $items = explode($delimiter, $value);
                $items_trim = array();
                foreach ($items as $item) {
                    $trimmed = trim($item);
                    if (!empty($trimmed)) {
                        $items_trim[] = $trimmed;
                    }
                }
                if (!empty($items_trim)) {
                    $flattened_asset["$key"] = $items_trim;
                }
            }
        }
    }
}

function get_ss_asset_list(&$ss, $project_id, $date_field)
{
    $assets = $ss->project_asset_list_values($project_id, $date_field);
    $count = count($assets);
    $asset_count = $ss->project_assets_count($project_id);
    if ($count != $asset_count) {
        throw new Exception("get_ss_asset_list got the wrong number of assets: $count counted, $asset_count expected.", 1);
    }

    return $assets;
}

function copy_pdf_to_s3($projectid, $filename, $source_url, $method, $log)
{
    try {
        $s3_bucket = 's3://digital-assets.library.cornell.edu';
        $s3_path = "$projectid/$filename.pdf";
        $s3_target = "$s3_bucket/$s3_path";
        $s3cmd = '/cul/share/miniconda/bin/s3cmd';
        //$s3cmd = '/usr/local/bin/s3cmd';  // local testing version
        //$log->note("method: $method");
        if ('update' == $method) {
            // check if it already exists on S3
            $command = "$s3cmd ls $s3_target";
            $output = '';
            $return_var = 0;
            //$log->note("list: $command");
            $lastline = exec($command, $output, $return_var);
            if (0 != $return_var) {
                $output[] = 'Command failed: '.$command;
                $out = implode('PHP_EOL', $output);
                throw new Exception("Error Processing checking for pdf on s3: $out", 1);
            }
            if (false !== strpos($lastline, $s3_path)) {
                // assume this image has already been processed
                return true;
            }
        }

        // make a local copy of the file
        $real_url = get_url_redirected($source_url);
        if (false === ($img = file_get_contents($real_url))) {
            throw new Exception("Unable to read file $source_url", 1);
        }
        $tmpfname = '/tmp/'.md5($projectid.$filename).'.pdf';
        if (false === ($fd = fopen($tmpfname, 'x+'))) {
            throw new Exception("Cannot create temp file $tmpfname", 1);
        }
        $bytes = fwrite($fd, $img);
        fclose($fd);
        if (false === $bytes) {
            throw new Exception('Cannot write to temp file', 1);
        }

        // copy the file
        $command = "$s3cmd put $tmpfname $s3_target";
        $output = '';
        $return_var = 0;
        //$log->note("put: $command");
        $lastline = exec($command, $output, $return_var);
        // delete temp file
        unlink($tmpfname);

        if (0 != $return_var) {
            $output[] = 'Command failed: '.$command;
            $out = implode('PHP_EOL', $output);
            throw new Exception("Error Processing putting pdf on s3: $out", 1);
        }
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage()."\n";
        $log->note($error);

        return false;
    }

    return true;
}

function extension_to_format($media_url, $file_extension) {
    // return an asset format suitable for format_tesim
    $mimes = new \Mimey\MimeTypes;
    $type = $mimes->getMimeType($file_extension);
    $parts = \explode('/', $type);

    switch ($parts[0]) {
    case 'image':
        $format = 'Image';
        break;
    case 'text':
        $format = 'Text';
        break;
    case 'audio':
        $format = 'Audio';
        break;
    case 'application':
        switch ($parts[1]) {
        case 'pdf':
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($media_url);
                $text = $pdf->getText();
                if (true === empty($text)) {
                    $format = 'Image';
                } else {
                    $format = 'Text';
                }
            } catch (\Throwable $th) {
                //$this->logger->warning('Problem pdf: ', [$th->getMessage(), $asset_id]);
                $format = 'Image';
            } catch (exception $th) {
                $format = 'Image';
            }
    break;

        default:
            $format = 'Other';
            break;
        }
        break;

    default:
        $format = 'Other';
        break;
    }

    return $format;
}

$log = false;

$options = getopt('p:s:n:', array('help', 'force', 'no-write', 'use-dev-solr', 'skip', 'extract'));

if (false === $options || isset($options['help'])) {
    usage();
}
$force_replacement = isset($options['force']);
$skip_this_collection = isset($options['skip']);
$extract_files = isset($options['extract']);
$do_not_write_to_solr = isset($options['no-write']);
$solr_collection_override = isset($options['use-dev-solr']) ?
  'http://jrc88.solr.library.cornell.edu/solr/digitalcollections_dev' : false;
if (isset($options['p'])) {
    if (is_numeric($options['p'])) {
        $single_collection = $options['p'];
    } else {
        usage();
    }
} else {
    $single_collection = false;
}
if (isset($options['s'])) {
    if (is_numeric($options['s'])) {
        $starting_asset = $options['s'];
    } else {
        usage();
    }
} else {
    $starting_asset = 0;
}
if (isset($options['n'])) {
    if (is_numeric($options['n'])) {
        $max_processing_count = $options['n'];
    } else {
        usage();
    }
} else {
    $max_processing_count = false; // this means process them all
}

$option_text = $single_collection ? "project $single_collection " : 'all ';
$option_text .= $force_replacement ? 'force ' : '';
$option_text .= $do_not_write_to_solr ? 'no-write ' : '';
$option_text .= $solr_collection_override ? 'use-dev-solr ' : '';

try {
    // batch process information
    $task = parse_ini_file('sharedshelf-to-solr.ini', true);
    if (false === $task) {
        echo "Need sharedshelf-to-solr.ini\n";
        exit(1);
    }

    // open log
    if (empty($task['process']['log_file_prefix'])) {
        echo "Need log_file_prefix\n";
        exit(1);
    }

    // sharedshelf user
    $user = parse_ini_file('ssUser.ini');
    if (false === $user) {
        throw new Exception('Need to create ssUser.ini. See README.md', 1);
    }

    if (!isset($task['process']['cookie_jar_path'])) {
        throw new Exception('Expecting cookie_jar_path in .ini file', 1);
    }

    $ss = new SharedShelfService($user['email'], $user['password'], $task['process']['cookie_jar_path']);

    $found_project = false;
    foreach ($task['configuration_files']['config'] as $config) {
        $project = parse_ini_file($config);
        if (false === $project) {
            throw new Exception("Missing configuration file: $config", 1);
        }
        if (false !== $single_collection) {
            if ($project['project'] != $single_collection) {
                // skip any other collection if one is listed on the command line
                continue;
            }
            if ($skip_this_collection) {
                // skip any collection with the --skip flag
                continue;
            }
            $found_project = true;
        }

        $project_id = $project['project'];

        // check valid values for flags
        if (!empty($project['copy_pdf_to_s3'])) {
            $value = $project['copy_pdf_to_s3'];
            switch ($value) {
        case 'update':
        case 'overwrite':
          // good options
          break;

        default:
          throw new Exception("invalid copy_pdf_to_s3 value for project  $project_id: $value", 1);
          break;
      }
        }

        // create a log file for this collection
        $log_file_prefix = $task['process']['log_file_prefix'].'-'.$project_id;
        $log = new SharedShelfToSolrLogger($log_file_prefix);
        $log->task("$config-$project_id");

        $log->note('SolrUpdater');
        $solr_url = (false !== $solr_collection_override) ? $solr_collection_override : $project['solr'];
        $log->note($solr_url);
        $solr = new SolrUpdater($solr_url, $config);

        // list of the ids already in solr
        $solr_asset_id_list = $solr->get_all_ids($project_id);
        $solr_asset_ids_to_delete = array_flip($solr_asset_id_list);

        $log->note('project_asset_ids');
        $asset_count = $ss->project_assets_count($project_id);
        $log->note("asset_count:$asset_count");
        echo "Processing: $option_text $config asset count: $asset_count ".$log->log_file_name().PHP_EOL;
        $asset_list = get_ss_asset_list($ss, $project_id, 'updated_on');

        // extract list of sharedshelf field names that need special array treatment
        $delimited_fields = empty($project['delimited_field']) ? array() : $project['delimited_field'];

        // find the publishing target id for this project
        if (empty($project['publishing_target_id'])) {
            $publishing_target_id = $ss->find_publishing_target_id($project_id);
        } else {
            // this comes from the publishing_target_id of the publishing_status field
            $publishing_target_id = $project['publishing_target_id'];
        }

        $solr_assets = array(); // accumulate assets for solr here

        $counter = 1;
        $assets_processed = 0;
        foreach ($asset_list as $asset_id => $updated_date) {
            $ss_id = $asset_id;
            $solr_id = 'ss:'.$asset_id;

            // eliminate asset ids that still exist in sharedshelf from the delete list
            unset($solr_asset_ids_to_delete["$solr_id"]);

            if ($asset_id < $starting_asset) {
                ++$counter;
                continue;
            }
            if ((false !== $max_processing_count) && ($assets_processed++ >= $max_processing_count)) {
                throw new Exception('Reached the maximum count specified on the -n argument', 1);
            }
            try {
                $ss_date = trim($updated_date);

                $log->item("asset $solr_id");
                $pct = sprintf('%01.2f', $counter++ * 100.0 / (float) $asset_count);
                $log->note("Completed:$pct");

                $max_attempt = 4;
                for ($attempt = 1; $attempt <= $max_attempt; ++$attempt) {
                    $log->note("Attempt:$attempt");
                    try {
                        /**
                         * Find any existing solr asset so we can preserve
                         * data others may have stored there.
                         */
                        $solr_in = $solr->get_item($solr_id);

                        if (TESTING_VERSION_CONFLICT === true && !empty($solr_in)) {
                            $log->note("version conflict test $attempt");
                            $fake_index = 'jgr25_content_test_tesim';
                            $fake_value = 'flibberdejibbit 897';
                            if (1 == $attempt) {
                                // writing back at this point to
                                // bump solr's version number
                                $solr_assets = array($solr_in);
                                $solr->add($solr_assets);
                            }
                        }

                        // grab the record from sharedshelf
                        $asset_full = $ss->asset($asset_id);

                        // determine publishing status - status_ssi
                        if (isset($asset_full['publishing_status']["$publishing_target_id"]['status'])) {
                            $cul_publishing_status = $asset_full['publishing_status']["$publishing_target_id"]['status'];
                        } else {
                            $cul_publishing_status = 'Unpublished';
                        }

                        // decide if we need to deal with this asset at all
                        if ($force_replacement) {
                            $log->note('Job:Replace');
                        } else {
                            if (empty($solr_in)) {
                                $log->note('Job:AddNew');
                            } else {
                                // compare publishing status
                                if (0 != strcmp($solr_in['status_ssi'], $cul_publishing_status)) {
                                    $log->note('Job:Publishing-status-change');
                                } else {
                                    // compare the dates
                                    if (empty($solr_in['updated_on_ss'])) {
                                        $log->note('Job:solr-missing-updated_on');
                                    } else {
                                        $solr_date = trim($solr_in['updated_on_ss']);
                                        if (0 == strcmp($ss_date, $solr_date)) {
                                            // dates match - skip this record
                                            $log->note('Job:Skip-DatesMatch');
                                            throw new DatesMatchException('Skip-DatesMatch', 1);
                                        }
                                        $log->note('Job:Update');
                                    }
                                }
                            }
                        }
                        $log->note(print_r($cul_publishing_status, true));

                        // prepare the sharedshelf record for solr
                        $asset = $ss->asset_field_values($asset_full);

                        split_delimited_fields($asset, $delimited_fields);
                        $solr_out = $solr->convert_ss_names_to_solr($asset);

                        // store the mime type for the individual asset,
                        // overriding collection-wide format_tesim from .ini file
                        $log->note('get media');
                        $media_url = $ss->media_url($ss_id);
                        $media_file_extension = $ss->media_file_extension($ss_id);
                        $solr_out['format_tesim'] = extension_to_format($media_url, $media_file_extension);

                        // check if we need images and their derivatives
                        $need_images = (isset($project['has_images']) && (0 == strcmp($project['has_images'], 'no'))) ? false : true;
                        if ($need_images) {
                            $solr_out['media_URL_tesim'] = $media_url;
                            $filename = $ss->media_filename($ss_id) . '.' . $media_file_extension;
                            $solr_out['filename_s'] = $filename;
                            $log->note('get derivatives');
                            for ($size = 0; $size <= 4; ++$size) {
                                $fld = 'media_URL_size_'.$size.'_tesim';
                                $solr_out["$fld"] = $ss->media_derivative_url($media_url, $size);
                            }

                            $log->note('get dimensions');
                            if (false !== ($dim = $ss->media_dimensions($ss_id))) {
                                $solr_out['img_width_tesim'] = $dim['width'];
                                $solr_out['img_height_tesim'] = $dim['height'];
                            }
                            $jsondetails = $ss->media_iiif_info($ss_id);
                            if (!empty($jsondetails)) {
                                $iiif_url = $jsondetails['info_url'];
                                if ('http' == parse_url($iiif_url, PHP_URL_SCHEME)) {
                                    $iiif_url = 'https'.substr($iiif_url, 4);
                                }
                                $solr_out['content_metadata_image_iiif_info_ssm'] = $iiif_url;
                                $solr_out['content_metadata_first_image_width_ssm'] = $jsondetails['width'];
                                $solr_out['content_metadata_first_image_height_ssm'] = $jsondetails['height'];
                            }

                            if (!empty($project['copy_pdf_to_s3'])) {
                                if ('pdf' == $media_file_extension) {
                                    $log->note('copying pdf to s3');
                                    $method = $force_replacement ? 'overwrite' : $project['copy_pdf_to_s3'];
                                    $filename = $ss->media_filename($ss_id);
                                    if (!copy_pdf_to_s3($project_id, $filename, $media_url, $method, $log)) {
                                        throw new Exception('Failed to copy pdf to s3', 1);
                                    }
                                } else {
                                    $log->note("not a pdf: $media_file_extension");
                                }
                            }
                        }

                        // add in the publishing status field
                        $solr_out['status_ssi'] = $cul_publishing_status;

                        // be sure the id field is the solr id not the sharedshelf one
                        $solr_out['id'] = $solr_id;

                        // remove any fields that will become "" in solr
                        $solr_out_full = array();
                        foreach ($solr_out as $key => $value) {
                            if (!empty($value) || false === $value) {
                                if (!is_array($value)) {
                                    $value = trim($value);
                                    // just a pair of double quotes?
                                    if (0 == strcmp($value, '""')) {
                                        $value = '';
                                    } else {
                                        // attempt to remove double quotes from decimal numbers (used to prevent rounding in SS)
                                        $value = preg_replace('/^\"([0-9]*\.[0-9]*)\"$/', '\1', $value);
                                    }
                                }
                                if (!empty($value)) {
                                    $solr_out_full["$key"] = $value;
                                }
                            }
                        }
                        if (false === $do_not_write_to_solr) {
                            if ($extract_files) {
                                if ('pdf' == $media_file_extension) {
                                    $log->note('extracting file content');
                                    $extracted_text = $solr->extract_only($media_url);
                                    $solr_out_full['text_tsimv'] = $extracted_text;
                                } else {
                                    $log->note("No extract for $media_file_extension");
                                }
                            }

                            // add this asset to solr
                            $log->note('adding to solr');
                            // ignore current contents of solr document ($solr_in)
                            $merged = $solr_out_full;
                            $solr_assets = array($merged);

                            $result = $solr->add($solr_assets);
                        }

                        // if we reach here, the record has been written to solr
                        // during this attempt, or we're not writing,
                        // so we just continue with the foreach loop
                        $log->note('add to solr done');
                        break;
                    } catch (DatesMatchException $e) {
                        // no change in the sharedshelf record, so
                        // continue with the outer foreach loop
                        // grabbing the next sharedshelf record
                        break;
                    } catch (VersionConflictException $e) {
                        // someone else changed the solr record while
                        // we were processing it - try again
                        $log->note('Version conflict.');
                        sleep(2);
                        continue;
                    } catch (ForumRequestException $e) {
                        // problem reading from JStor Forum
                        // try again
                        $log->note('Could not read from Forum.');
                        sleep(3);
                        continue;
                    } catch (Exception $e) {
                        // just pass on other exceptions
                        throw $e;
                    }
                }

                if ($attempt >= $max_attempt) {
                    throw new Exception("Unable to process asset $solr_id after $max_attempt attempts.", 1);
                }
            } catch (Exception $e) {
                $error = 'Caught exception: '.$e->getMessage()." - skipping this asset\n";
                if (false !== $log) {
                    $log->error($error);
                } else {
                    echo $error;
                }
            }
        }

        // delete assets from solr that are no longer in sharedshelf
        if (false === $do_not_write_to_solr) {
            if (!empty($solr_asset_ids_to_delete)) {
                $ids = array_flip($solr_asset_ids_to_delete);
                $id_count = count($ids);
                $batch_size = 20;
                for ($batch = 0; $batch < $id_count; $batch += $batch_size) {
                    $id_set = array_slice($ids, $batch, $batch_size);
                    $log->note('Deleting solr ids: ' . implode(', ', $id_set) . "\n");
                    $solr->delete_items($id_set);
                }
            } else {
                $log->note("No solr asssets to delete for project $project_id.");
            }
        }

        //print_r($task);
        $log->task('Done.');
    }

    if (false !== $single_collection && !$found_project) {
        throw new Exception("Collection $single_collection is missing from sharedshelf-to-solr.ini", 1);
    }
} catch (Exception $e) {
    $error = 'Caught exception: '.$e->getMessage()."\n";
    if (false !== $log) {
        $log->error($error);
    } else {
        echo $error;
    }
    exit(1);
}
exit(0);
