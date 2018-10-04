<?php
require_once('../SharedShelfService.php');

class SharedShelfMetadataApplicationProfile {

    private $sss = '';   // instance of SharedShelfService
    private $project = FALSE;
    private $metadata = FALSE;
    private $raw_fields = FALSE;
    private $project_fields = FALSE;
    private $ss2map = FALSE;

    private $map_fields = array(
        "Address" => array( 'map_name' => "Address", 'solr_name' => "map_address", 'multivalued' => TRUE, 'type' => "string" ),
        "Agent" => array( 'map_name' => "Agent", 'solr_name' => "map_agent", 'multivalued' => TRUE, 'type' => "string" ),
        "Agent_Role" => array( 'map_name' => "Agent_Role", 'solr_name' => "map_agent_role", 'multivalued' => TRUE, 'type' => "string" ),
        "Alternate Title" => array( 'map_name' => "Alternate Title", 'solr_name' => "map_alternate_title", 'multivalued' => FALSE, 'type' => "string" ),
        "Annotation" => array( 'map_name' => "Annotation", 'solr_name' => "map_annotation", 'multivalued' => FALSE, 'type' => "string" ),
        "Archival Collection" => array( 'map_name' => "Archival Collection", 'solr_name' => "map_archival_collection", 'multivalued' => FALSE, 'type' => "string" ),
        "Archival Finding Aid" => array( 'map_name' => "Archival Finding Aid", 'solr_name' => "map_archival_finding_aid", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Display" => array( 'map_name' => "Artstor Classification Display", 'solr_name' => "map_artstor_classification_display", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Facet" => array( 'map_name' => "Artstor Classification Facet", 'solr_name' => "map_artstor_classification_facet", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Link" => array( 'map_name' => "Artstor Classification Link", 'solr_name' => "map_artstor_classification_link", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Digital Publisher" => array( 'map_name' => "Artstor Collection Digital Publisher", 'solr_name' => "map_artstor_collection_digital_publisher", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Name" => array( 'map_name' => "Artstor Collection Name", 'solr_name' => "map_artstor_collection_name", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Publication Date" => array( 'map_name' => "Artstor Collection Publication Date", 'solr_name' => "map_artstor_collection_publication_date", 'multivalued' => FALSE, 'type' => "date" ),
        "Artstor Collection Status" => array( 'map_name' => "Artstor Collection Status", 'solr_name' => "map_artstor_collection_status", 'multivalued' => FALSE, 'type' => "string" ),
        "ARTstor Id" => array( 'map_name' => "ARTstor Id", 'solr_name' => "map_artstor_id", 'multivalued' => FALSE, 'type' => "string" ),
        "Bibliography" => array( 'map_name' => "Bibliography", 'solr_name' => "map_bibliography", 'multivalued' => TRUE, 'type' => "string" ),
        "Box" => array( 'map_name' => "Box", 'solr_name' => "map_box", 'multivalued' => FALSE, 'type' => "string" ),
        "Cataloger" => array( 'map_name' => "Cataloger", 'solr_name' => "map_cataloger", 'multivalued' => FALSE, 'type' => "string" ),
        "Cite As" => array( 'map_name' => "Cite As", 'solr_name' => "map_cite_as", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Level Bib" => array( 'map_name' => "Collection Level Bib", 'solr_name' => "map_collection_level_bib", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Sequence" => array( 'map_name' => "Collection Sequence", 'solr_name' => "map_collection_sequence", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Website" => array( 'map_name' => "Collection Website", 'solr_name' => "map_collection_website", 'multivalued' => FALSE, 'type' => "string" ),
        "Condition" => array( 'map_name' => "Condition", 'solr_name' => "map_condition", 'multivalued' => FALSE, 'type' => "string" ),
        "Country" => array( 'map_name' => "Country", 'solr_name' => "map_country", 'multivalued' => TRUE, 'type' => "string" ),
        "Created By" => array( 'map_name' => "Created By", 'solr_name' => "map_created_by", 'multivalued' => TRUE, 'type' => "string" ),
        "Created On" => array( 'map_name' => "Created On", 'solr_name' => "map_created_on", 'multivalued' => FALSE, 'type' => "date" ),
        "Culture" => array( 'map_name' => "Culture", 'solr_name' => "map_culture", 'multivalued' => TRUE, 'type' => "string" ),
        "Date" => array( 'map_name' => "Date", 'solr_name' => "map_date", 'multivalued' => TRUE, 'type' => "tdate" ),
        "Date_Type" => array( 'map_name' => "Date_Type", 'solr_name' => "map_date_type", 'multivalued' => TRUE, 'type' => "string" ),
        "DCMI Type" => array( 'map_name' => "DCMI Type", 'solr_name' => "map_dcmi_type", 'multivalued' => FALSE, 'type' => "string" ),
        "Description" => array( 'map_name' => "Description", 'solr_name' => "map_description", 'multivalued' => TRUE, 'type' => "string" ),
        "Disable Download" => array( 'map_name' => "Disable Download", 'solr_name' => "map_disable_download", 'multivalued' => FALSE, 'type' => "int" ),
        "Earliest Date" => array( 'map_name' => "Earliest Date", 'solr_name' => "map_earliest_date", 'multivalued' => FALSE, 'type' => "int" ),
        "Elevation" => array( 'map_name' => "Elevation", 'solr_name' => "map_elevation", 'multivalued' => FALSE, 'type' => "string" ),
        "Event" => array( 'map_name' => "Event", 'solr_name' => "map_event", 'multivalued' => TRUE, 'type' => "string" ),
        "Exhibition" => array( 'map_name' => "Exhibition", 'solr_name' => "map_exhibition", 'multivalued' => TRUE, 'type' => "string" ),
        "Filename" => array( 'map_name' => "Filename", 'solr_name' => "map_filename", 'multivalued' => TRUE, 'type' => "string" ),
        "Folder" => array( 'map_name' => "Folder", 'solr_name' => "map_folder", 'multivalued' => FALSE, 'type' => "string" ),
        "Identifier" => array( 'map_name' => "Identifier", 'solr_name' => "map_identifier", 'multivalued' => TRUE, 'type' => "string" ),
        "Identifier_Type" => array( 'map_name' => "Identifier_Type", 'solr_name' => "map_identifier_type", 'multivalued' => TRUE, 'type' => "string" ),
        "Image View Description" => array( 'map_name' => "Image View Description", 'solr_name' => "map_image_view_description", 'multivalued' => TRUE, 'type' => "string" ),
        "Image View Type" => array( 'map_name' => "Image View Type", 'solr_name' => "map_image_view_type", 'multivalued' => TRUE, 'type' => "string" ),
        "Inscription" => array( 'map_name' => "Inscription", 'solr_name' => "map_inscription", 'multivalued' => TRUE, 'type' => "string" ),
        "isTranslatedAs" => array( 'map_name' => "isTranslatedAs", 'solr_name' => "map_istranslatedas", 'multivalued' => TRUE, 'type' => "int" ),
        "isTranslationOf" => array( 'map_name' => "isTranslationOf", 'solr_name' => "map_istranslationof", 'multivalued' => TRUE, 'type' => "int" ),
        "Kaltura ID" => array( 'map_name' => "Kaltura ID", 'solr_name' => "map_kaltura_id", 'multivalued' => TRUE, 'type' => "string" ),
        "Kaltura Playlist" => array( 'map_name' => "Kaltura Playlist", 'solr_name' => "map_kaltura_playlist", 'multivalued' => FALSE, 'type' => "string" ),
        "Keywords" => array( 'map_name' => "Keywords", 'solr_name' => "map_keywords", 'multivalued' => TRUE, 'type' => "string" ),
        "Language" => array( 'map_name' => "Language", 'solr_name' => "map_language", 'multivalued' => TRUE, 'type' => "string" ),
        "Latest Date" => array( 'map_name' => "Latest Date", 'solr_name' => "map_latest_date", 'multivalued' => FALSE, 'type' => "int" ),
        "Latitude" => array( 'map_name' => "Latitude", 'solr_name' => "map_latitude", 'multivalued' => FALSE, 'type' => "location" ),
        "Legacy_Label" => array( 'map_name' => "Legacy_Label", 'solr_name' => "map_legacy_label", 'multivalued' => TRUE, 'type' => "string" ),
        "Legacy_Value" => array( 'map_name' => "Legacy_Value", 'solr_name' => "map_legacy_value", 'multivalued' => TRUE, 'type' => "string" ),
        "Linked Data Updated On" => array( 'map_name' => "Linked Data Updated On", 'solr_name' => "map_linked_data_updated_on", 'multivalued' => FALSE, 'type' => "date" ),
        "Location" => array( 'map_name' => "Location", 'solr_name' => "map_location", 'multivalued' => TRUE, 'type' => "string" ),
        "Longitude" => array( 'map_name' => "Longitude", 'solr_name' => "map_longitude", 'multivalued' => FALSE, 'type' => "location" ),
        "Materials/Techniques" => array( 'map_name' => "Materials/Techniques", 'solr_name' => "map_materials_techniques", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement" => array( 'map_name' => "Measurement", 'solr_name' => "map_measurement", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement_Dimension" => array( 'map_name' => "Measurement_Dimension", 'solr_name' => "map_measurement_dimension", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement_Unit" => array( 'map_name' => "Measurement_Unit", 'solr_name' => "map_measurement_unit", 'multivalued' => TRUE, 'type' => "string" ),
        "Metadata Update Date" => array( 'map_name' => "Metadata Update Date", 'solr_name' => "map_metadata_update_date", 'multivalued' => FALSE, 'type' => "date" ),
        "Notes" => array( 'map_name' => "Notes", 'solr_name' => "map_notes", 'multivalued' => TRUE, 'type' => "string" ),
        "OCR Text" => array( 'map_name' => "OCR Text", 'solr_name' => "map_ocr_text", 'multivalued' => TRUE, 'type' => "string" ),
        "PreservationCollectionID" => array( 'map_name' => "PreservationCollectionID", 'solr_name' => "map_preservationcollectionid", 'multivalued' => FALSE, 'type' => "string" ),
        "PreservationItemID" => array( 'map_name' => "PreservationItemID", 'solr_name' => "map_preservationitemid", 'multivalued' => FALSE, 'type' => "string" ),
        "PreservationSystem" => array( 'map_name' => "PreservationSystem", 'solr_name' => "map_preservationsystem", 'multivalued' => FALSE, 'type' => "string" ),
        "Provenance" => array( 'map_name' => "Provenance", 'solr_name' => "map_provenance", 'multivalued' => TRUE, 'type' => "string" ),
        "Publish to Portal" => array( 'map_name' => "Publish to Portal", 'solr_name' => "map_publish_to_portal", 'multivalued' => FALSE, 'type' => "boolean" ),
        "References" => array( 'map_name' => "References", 'solr_name' => "map_references", 'multivalued' => TRUE, 'type' => "string" ),
        "Related Work" => array( 'map_name' => "Related Work", 'solr_name' => "map_related_work", 'multivalued' => TRUE, 'type' => "string" ),
        "Relationships" => array( 'map_name' => "Relationships", 'solr_name' => "map_relationships", 'multivalued' => TRUE, 'type' => "string" ),
        "Repository" => array( 'map_name' => "Repository", 'solr_name' => "map_repository", 'multivalued' => FALSE, 'type' => "string" ),
        "Rights" => array( 'map_name' => "Rights", 'solr_name' => "map_rights", 'multivalued' => FALSE, 'type' => "string" ),
        "Set Title" => array( 'map_name' => "Set Title", 'solr_name' => "map_set_title", 'multivalued' => FALSE, 'type' => "string" ),
        "Shared Shelf Collection Digital Publisher" => array( 'map_name' => "Shared Shelf Collection Digital Publisher", 'solr_name' => "map_shared_shelf_collection_digital_publisher", 'multivalued' => FALSE, 'type' => "string" ),
        "Shared Shelf Collection Publication Date" => array( 'map_name' => "Shared Shelf Collection Publication Date", 'solr_name' => "map_shared_shelf_collection_publication_date", 'multivalued' => FALSE, 'type' => "date" ),
        "Shared Shelf Collection Status" => array( 'map_name' => "Shared Shelf Collection Status", 'solr_name' => "map_shared_shelf_collection_status", 'multivalued' => FALSE, 'type' => "string" ),
        "Site" => array( 'map_name' => "Site", 'solr_name' => "map_site", 'multivalued' => TRUE, 'type' => "string" ),
        "Source" => array( 'map_name' => "Source", 'solr_name' => "map_source", 'multivalued' => TRUE, 'type' => "string" ),
        "Species" => array( 'map_name' => "Species", 'solr_name' => "map_species", 'multivalued' => TRUE, 'type' => "string" ),
        "SSID" => array( 'map_name' => "SSID", 'solr_name' => "map_ssid", 'multivalued' => FALSE, 'type' => "string" ),
        "Style/Period" => array( 'map_name' => "Style/Period", 'solr_name' => "map_style_period", 'multivalued' => TRUE, 'type' => "string" ),
        "Subject" => array( 'map_name' => "Subject", 'solr_name' => "map_subject", 'multivalued' => TRUE, 'type' => "string" ),
        "Thumbnail" => array( 'map_name' => "Thumbnail", 'solr_name' => "map_thumbnail", 'multivalued' => TRUE, 'type' => "int" ),
        "Title" => array( 'map_name' => "Title", 'solr_name' => "map_title", 'multivalued' => TRUE, 'type' => "string" ),
        "Title_Language" => array( 'map_name' => "Title_Language", 'solr_name' => "map_title_language", 'multivalued' => TRUE, 'type' => "string" ),
        "Transcription" => array( 'map_name' => "Transcription", 'solr_name' => "map_transcription", 'multivalued' => FALSE, 'type' => "string" ),
        "Translation" => array( 'map_name' => "Translation", 'solr_name' => "map_translation", 'multivalued' => FALSE, 'type' => "string" ),
        "Updated By" => array( 'map_name' => "Updated By", 'solr_name' => "map_updated_by", 'multivalued' => TRUE, 'type' => "string" ),
        "Updated On" => array( 'map_name' => "Updated On", 'solr_name' => "map_updated_on", 'multivalued' => TRUE, 'type' => "date" ),
        "Venue" => array( 'map_name' => "Venue", 'solr_name' => "map_venue", 'multivalued' => TRUE, 'type' => "string" ),
        "Volume/Issue" => array( 'map_name' => "Volume/Issue", 'solr_name' => "map_volume_issue", 'multivalued' => FALSE, 'type' => "string" ),
        "Work Sequence" => array( 'map_name' => "Work Sequence", 'solr_name' => "map_work_sequence", 'multivalued' => FALSE, 'type' => "string" ),
        "Work Type" => array( 'map_name' => "Work Type", 'solr_name' => "map_work_type", 'multivalued' => TRUE, 'type' => "string" ),
    );

    function __construct($ss) {
        $this->sss = $ss; // fully built SharedShelfService object
    }

    function set_project($project_id) {
        try {
            $this->project = $project_id;
            $this->metadata = $this->sss->project_metadata($this->project);
            $this->project_fields2();
        }
        catch (Exception $e) {
            echo 'set_project caught exception: ',  $e->getMessage(), "\n";
        } 
    }

    private function project_fields2() {
        $fields = array();
        foreach ($this->metadata['columns'] as $column) {
            $col = array();
            $col['dataIndex'] = $column['dataIndex'];
            $col['header'] = $column['header'];

            // repeating fields
            $map_field = $column['header'];
            $map_field_base = preg_replace('/[0-9]+/', '', $map_field);
            if ($map_field == $map_field_base) {
                $map_field_no = 1;
            }
            else {
                $map_field_no = preg_replace('/^.+([0-9]+).*/', '$1', $map_field);
            }

            // lookup solr field name
            if (isset($this->map_fields["$map_field_base"])) {
                if ($this->map_fields["$map_field_base"]['multivalued'] && $map_field_no > 1) {
                    $col['order'] = $map_field_no;
                    $solr_field = $this->map_fields["$map_field_base"]["solr_name"];
                    $solr_field = preg_replace('/_([^_]+)(_?.?+)/', '_${1}' . $map_field_no . '${2}', $solr_field);
                }
                else {
                    $solr_field = $this->map_fields["$map_field_base"]["solr_name"];
                }
                $col['solr'] = $solr_field;
                $fields[$column['dataIndex']] = $col;
            }
            else {
                echo "Field missing from MAP: $map_field_base\n";
            }

        }
        $this->ss2map = $fields;
    }

    private function get_solr_extension($solr_base_name) {
        switch ($solr_base_name) {
            case 'collection_sequence': $extension = 'isi'; break;
            case 'created_on':          $extension = 'tsi'; break;
            case 'id':                  $extension = ''; break;
            case 'map_agent_role':      $extension = ''; break; 
            case 'project_id':          $extension = 'ssi'; break;
            case 'updated_on':          $extension = 'ss'; break;
            
            default:
                $extension = 'tesim'
                break;
        }
        return $extension;
    }

    private function project_fields() {
        // map sharedshelf field names to MAP field names
        //print_r($this->metadata);
        $this->project_fields = array();
        foreach ($this->metadata['columns'] as $column) {
            $map_field = $column['header'];
            $map_field_base = preg_replace('/[0-9]+/', '', $map_field);
            if ($map_field == $map_field_base) {
                $map_field_no = 1;
            }
            else {
                $map_field_no = preg_replace('/^.+([0-9]+).*/', '$1', $map_field);
            }
            $ss_field = $column['dataIndex'];
            if (isset($this->map_fields["$map_field_base"])) {
                $solr_field = $this->map_fields["$map_field_base"]["solr_name"];
                if ($this->map_fields["$map_field_base"]['multivalued']) {
                    $this->ss2map["$ss_field"] = array('solr' => "$solr_field", 'order' => $map_field_no);
                }
                else if (isset($this->project_fields["$map_field_base"])) {
                    throw new Exception("$map_field_base defined twice: $ss_field", 1);                    
                }
                else {
                    $this->ss2map["$ss_field"] = array('solr' => $solr_field);
                }
            }
            else {
                echo "Unknown field: $map_field_base -> $ss_field\n";               
            }
        }
        //print_r($this->ss2map);

        // eliminate unindexed fields
    }

    function get_map() {
        return $this->ss2map;
    }

    function get_raw_fields() {
        return $this->raw_fields;
    }

    function get_asset($id) {
        // return the asset with solr keys
        $solr = array();
        $asset = $this->sss->asset($id);
        foreach ($asset as $key => $value) {
            if (isset($value['display_value'])) {
                // handle controlled lists
                $value = $value['display_value'];
            }
            // handle pipe delimited fields
            if (!is_array($value)) {
                $multi = explode('|', $value);
                if (count($multi) > 1) {
                    $value = $multi;
                }
            }
            if (isset($this->ss2map["$key"])) {
                $solrkey = $this->ss2map["$key"]['solr'];
                echo "$key => $solrkey\n";
                if (isset($this->ss2map["$key"]['order'])) {
                    $order = $this->ss2map["$key"]['order'];
                    if (!is_array($solr["$solrkey"])) {
                        $solr["$solrkey"] = array("$order" => $value);
                    }
                    else {
                        $solr["$solrkey"]["$order"] = $value;
                    }
                }
                else {
                    $solr["$solrkey"] = $value;
                }

            }
            else {
                echo "unknown asset key $key\n";
            }
        }
        //print_r($solr);
        return $solr;
    }

    function get_map_as_ini() {
        // return the mapping formatted as a .ini file (see ss2solr.example.ini)
        $lines = array();
        $lines[] = ';; account configuration for ss2solr';
        $lines[] = 'solr = "http://jrc88.solr.library.cornell.edu/solr/digitalcollections"';
        $lines[] = ';; add the project ID from sharedshelf';
        $lines[] = 'project = "' . $this->project . '"';
        $lines[] = "";
        foreach($this->ss2map as $ssField => $info) {
            $lines[] = '; ' . $info['header'];
            $lines[] = 'fields[' . $ssField . '] = "' . $info['solr'] . '"';
            $lines[] = '';
        }
        return implode("\n", $lines);
    }

}
?>