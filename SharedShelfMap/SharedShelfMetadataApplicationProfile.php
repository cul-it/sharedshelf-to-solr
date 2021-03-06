<?php
require_once '../SharedShelfService.php' ;
require_once "readCSV.php";

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
    
    private $map_fields2 = array(
        "Address" => array( 'map_name' => "Address", 'solr_name' => "address_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Agent_Role" => array( 'map_name' => "Agent Role", 'solr_name' => "agent_role_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Agent" => array( 'map_name' => "Agent", 'solr_name' => "agent_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Alternate Title" => array( 'map_name' => "Alternate Title", 'solr_name' => "alternate_title_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Annotation" => array( 'map_name' => "Annotation", 'solr_name' => "annotation_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Archival Collection" => array( 'map_name' => "Archival Collection", 'solr_name' => "archival_collection_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Archival Finding Aid" => array( 'map_name' => "Archival Finding Aid", 'solr_name' => "finding_aid_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Display" => array( 'map_name' => "Artstor Classification Display", 'solr_name' => "artstor_classification_display_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Facet" => array( 'map_name' => "Artstor Classification Facet", 'solr_name' => "artstor_classification_facet_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Classification Link" => array( 'map_name' => "Artstor Classification Link", 'solr_name' => "artstor_classification_link_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Digital Publisher" => array( 'map_name' => "Artstor Collection Digital Publisher", 'solr_name' => "artstor_collection_digital_publisher_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Name" => array( 'map_name' => "Artstor Collection Name", 'solr_name' => "collection_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Artstor Collection Publication Date" => array( 'map_name' => "Artstor Collection Publication Date", 'solr_name' => "artstor_collection_publication_date_tesim", 'multivalued' => FALSE, 'type' => "date" ),
        "Artstor Collection Status" => array( 'map_name' => "Artstor Collection Status", 'solr_name' => "artstor_collection_status_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "ARTstor Id" => array( 'map_name' => "ARTstor Id", 'solr_name' => "identifier_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Bibliography" => array( 'map_name' => "Bibliography", 'solr_name' => "bibliography_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Box" => array( 'map_name' => "Box", 'solr_name' => "box_box_folder_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Cataloger" => array( 'map_name' => "Cataloger", 'solr_name' => "cataloger_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Cite As" => array( 'map_name' => "Cite As", 'solr_name' => "cite_as_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Level Bib" => array( 'map_name' => "Collection Level Bib", 'solr_name' => "collection_level_bib_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Sequence" => array( 'map_name' => "Collection Sequence", 'solr_name' => "collection_sequence_isi", 'multivalued' => FALSE, 'type' => "string" ),
        "Collection Website" => array( 'map_name' => "Collection Website", 'solr_name' => "collection_website_ss", 'multivalued' => FALSE, 'type' => "string" ),
        "Condition" => array( 'map_name' => "Condition", 'solr_name' => "condition_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Country" => array( 'map_name' => "Country", 'solr_name' => "country_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Created By" => array( 'map_name' => "Created By", 'solr_name' => "creator_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Created On" => array( 'map_name' => "Created On", 'solr_name' => "created_on_tsi", 'multivalued' => FALSE, 'type' => "date" ),
        "Culture" => array( 'map_name' => "Culture", 'solr_name' => "culture_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Date_Type" => array( 'map_name' => "Date_Type", 'solr_name' => "date_type_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Date" => array( 'map_name' => "Date", 'solr_name' => "date_tesim", 'multivalued' => TRUE, 'type' => "tdate" ),
        "DCMI Type" => array( 'map_name' => "DCMI Type", 'solr_name' => "dcmi_type_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Description" => array( 'map_name' => "Description", 'solr_name' => "description_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Disable Download" => array( 'map_name' => "Disable Download", 'solr_name' => "disable_download_tesim", 'multivalued' => FALSE, 'type' => "int" ),
        "Earliest Date" => array( 'map_name' => "Earliest Date", 'solr_name' => "earliest_date_isi", 'multivalued' => FALSE, 'type' => "int" ),
        "Elevation" => array( 'map_name' => "Elevation", 'solr_name' => "elevation_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Event" => array( 'map_name' => "Event", 'solr_name' => "event_name_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Exhibition" => array( 'map_name' => "Exhibition", 'solr_name' => "exhibition_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Filename" => array( 'map_name' => "Filename", 'solr_name' => "filename_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Folder" => array( 'map_name' => "Folder", 'solr_name' => "folder_box_folder_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "ID Number" => array( 'map_name' => "ID Number", 'solr_name' => "id_number_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Identifier_Type" => array( 'map_name' => "Identifier_Type", 'solr_name' => "identifier_type_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Identifier" => array( 'map_name' => "Identifier", 'solr_name' => "identifier_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Image View Description" => array( 'map_name' => "Image View Description", 'solr_name' => "image_view_desc_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Image View Type" => array( 'map_name' => "Image View Type", 'solr_name' => "image_view_type_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Inscription" => array( 'map_name' => "Inscription", 'solr_name' => "inscription_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "isTranslatedAs" => array( 'map_name' => "isTranslatedAs", 'solr_name' => "translation_as_tesim", 'multivalued' => TRUE, 'type' => "int" ),
        "isTranslationOf" => array( 'map_name' => "isTranslationOf", 'solr_name' => "translation_of_tesim", 'multivalued' => TRUE, 'type' => "int" ),
        "Kaltura ID" => array( 'map_name' => "Kaltura ID", 'solr_name' => "kaltura_id_s", 'multivalued' => TRUE, 'type' => "string" ),
        "Kaltura Playlist" => array( 'map_name' => "Kaltura Playlist", 'solr_name' => "kaltura_playlist_s", 'multivalued' => FALSE, 'type' => "string" ),
        "Keywords" => array( 'map_name' => "Keywords", 'solr_name' => "keywords_subject_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Language" => array( 'map_name' => "Language", 'solr_name' => "language_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Latest Date" => array( 'map_name' => "Latest Date", 'solr_name' => "latest_date_isi", 'multivalued' => FALSE, 'type' => "int" ),
        "Latitude" => array( 'map_name' => "Latitude", 'solr_name' => "latitude_tsi", 'multivalued' => FALSE, 'type' => "location" ),
        "Legacy_Label" => array( 'map_name' => "Legacy_Label", 'solr_name' => "legacy_label_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Legacy_Value" => array( 'map_name' => "Legacy_Value", 'solr_name' => "legacy_value_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Linked Data Updated On" => array( 'map_name' => "Linked Data Updated On", 'solr_name' => "linked_data_updated_on_tesim", 'multivalued' => FALSE, 'type' => "date" ),
        "Location" => array( 'map_name' => "Location", 'solr_name' => "location_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Longitude" => array( 'map_name' => "Longitude", 'solr_name' => "longitude_tsi", 'multivalued' => FALSE, 'type' => "location" ),
        "Materials/Techniques" => array( 'map_name' => "Materials/Techniques", 'solr_name' => "mat_tech_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement_Dimension" => array( 'map_name' => "Measurement_Dimension", 'solr_name' => "measurement_dimension_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement_Unit" => array( 'map_name' => "Measurement_Unit", 'solr_name' => "measurement_units_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Measurement" => array( 'map_name' => "Measurement", 'solr_name' => "measurement_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "media_count" => array( 'map_name' => "media_count", 'solr_name' => "media_count_ssi", 'multivalued' => FALSE, 'type' => "int" ),
        "Metadata Update Date" => array( 'map_name' => "Metadata Update Date", 'solr_name' => "metadata_update_date_tesim", 'multivalued' => FALSE, 'type' => "date" ),
        "Notes" => array( 'map_name' => "Notes", 'solr_name' => "notes_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "OCR Text" => array( 'map_name' => "OCR Text", 'solr_name' => "ocr_transcription_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "PreservationCollectionID" => array( 'map_name' => "PreservationCollectionID", 'solr_name' => "preservation_collection__id_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "PreservationItemID" => array( 'map_name' => "PreservationItemID", 'solr_name' => "preservation_item_id_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "PreservationSystem" => array( 'map_name' => "PreservationSystem", 'solr_name' => "preservationsystem_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "primary_image" => array( 'map_name' => "primary_image", 'solr_name' => "primary_image_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "project_id" => array( 'map_name' => "project_id", 'solr_name' => "project_id_ssi", 'multivalued' => FALSE, 'type' => "int" ),
        "Provenance" => array( 'map_name' => "Provenance", 'solr_name' => "provenance_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Publish to Portal" => array( 'map_name' => "Publish to Portal", 'solr_name' => "publish_to_portal_tesim", 'multivalued' => FALSE, 'type' => "boolean" ),
        "publishing_status" => array( 'map_name' => "publishing_status", 'solr_name' => "publishing_status_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "References" => array( 'map_name' => "References", 'solr_name' => "reference_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Related Work" => array( 'map_name' => "Related Work", 'solr_name' => "related_work_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Relationships" => array( 'map_name' => "Relationships", 'solr_name' => "relationships_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Repository" => array( 'map_name' => "Repository", 'solr_name' => "repository_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Rights" => array( 'map_name' => "Rights", 'solr_name' => "rights_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "sequence_number" => array( 'map_name' => "sequence_number", 'solr_name' => "sequence_number_tsi", 'multivalued' => FALSE, 'type' => "int" ),
        "Series" => array( 'map_name' => "Series", 'solr_name' => "series_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Set Title" => array( 'map_name' => "Set Title", 'solr_name' => "set_title_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Shared Shelf Collection Digital Publisher" => array( 'map_name' => "Shared Shelf Collection Digital Publisher", 'solr_name' => "shared_shelf_collection_digital_publisher_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Shared Shelf Collection Publication Date" => array( 'map_name' => "Shared Shelf Collection Publication Date", 'solr_name' => "shared_shelf_collection_publication_date_tesim", 'multivalued' => FALSE, 'type' => "date" ),
        "Shared Shelf Collection Status" => array( 'map_name' => "Shared Shelf Collection Status", 'solr_name' => "shared_shelf_collection_status_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Site" => array( 'map_name' => "Site", 'solr_name' => "site_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Source" => array( 'map_name' => "Source", 'solr_name' => "source_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Species" => array( 'map_name' => "Species", 'solr_name' => "species_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "SSID" => array( 'map_name' => "SSID", 'solr_name' => "ssid_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Style/Period" => array( 'map_name' => "Style/Period", 'solr_name' => "style_period_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Subject" => array( 'map_name' => "Subject", 'solr_name' => "subject_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Thumbnail" => array( 'map_name' => "Thumbnail", 'solr_name' => "thumbnail_tesim", 'multivalued' => TRUE, 'type' => "int" ),
        "Title_Language" => array( 'map_name' => "Title_Language", 'solr_name' => "title_language_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Title" => array( 'map_name' => "Title", 'solr_name' => "title_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Transcription" => array( 'map_name' => "Transcription", 'solr_name' => "transcription_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Translation" => array( 'map_name' => "Translation", 'solr_name' => "translation_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Updated By" => array( 'map_name' => "Updated By", 'solr_name' => "updated_by_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Updated On" => array( 'map_name' => "Updated On", 'solr_name' => "updated_on_ss", 'multivalued' => TRUE, 'type' => "date" ),
        "Venue" => array( 'map_name' => "Venue", 'solr_name' => "venue_tesim", 'multivalued' => TRUE, 'type' => "string" ),
        "Volume/Issue" => array( 'map_name' => "Volume/Issue", 'solr_name' => "vol_issue_no_tesim", 'multivalued' => FALSE, 'type' => "string" ),
        "Work Sequence" => array( 'map_name' => "Work Sequence", 'solr_name' => "sequence_number_tsi", 'multivalued' => FALSE, 'type' => "string" ),
        "Work Type" => array( 'map_name' => "Work Type", 'solr_name' => "work_type_tesim", 'multivalued' => TRUE, 'type' => "string" ),
    );

    private $collection_fields = array(
        "Media URL" => array( 'field_name' => "media_URL_tesim", 'solr_name' => "media_URL_tesim", 'multivalued' => FALSE, 'type' => "string"),
        "Media URL Size 0" => array( 'field_name' => "media_URL_size_0_s", 'solr_name' => "media_URL_size_0_tesim", 'multivalued' => FALSE, 'type' => "string"),
        "Media URL Size 1" => array( 'field_name' => "media_URL_size_1_s", 'solr_name' => "media_URL_size_1_tesim", 'multivalued' => FALSE, 'type' => "string"),
        "Media URL Size 2" => array( 'field_name' => "media_URL_size_2_s", 'solr_name' => "media_URL_size_2_tesim", 'multivalued' => FALSE, 'type' => "string"),
        "Media URL Size 3" => array( 'field_name' => "media_URL_size_3_s", 'solr_name' => "media_URL_size_3_tesim", 'multivalued' => FALSE, 'type' => "string"),
        "Media URL Size 4" => array( 'field_name' => "media_URL_size_4_s", 'solr_name' => "media_URL_size_4_tesim", 'multivalued' => FALSE, 'type' => "string"),
    );

    // note: both names are solr field names
    private $copy_fields = array(
        "Title" => array( 'source_name' => 'title_tesim', 'target_name' => 'full_title_tesim'),

    );

    // same value set for each record in collection
    // target_name is a solr field
    // source_column is a column in collection_metadata.csv
    private $set_solr_fields = array(
        "Collection ID" => array( 'target_name' => 'project_isi', 'source_column' => 'collection_id'),
        "Collection Name" => array( 'target_name' => 'collection_tesim', 'source_column' => 'collection_name'),
        "Collection Website URL" => array( 'target_name' => 'collection_website_ss', 'source_column' => 'collection_portal_path'),
        "Shared Shelf Commons URL" => array( 'target_name' => 'forum_website_tesim', 'source_column' => 'collection_ssc_url'),
        "Bib ID" => array( 'target_name' => 'bibid_ssi', 'source_column' => 'bib_id'),
        "Format" => array( 'target_name' => 'format_tesim', 'source_column' => 'format'),
        "Max Download" => array( 'target_name' => 'download_link_tesim', 'source_column' => 'max_download_size'),
        "Location Type" => array( 'source_column' => 'location_type'),
        "Creator Sort" => array( 'target_name' => 'author_t_tesim', 'source_column' => 'creator_sort', 'single_value' => true, ),
        "Title Sort" => array( 'target_name' => 'title_ssi', 'source_column' => 'title_sort', 'single_value' => true, ),
        "Publishing Target" => array( 'target_name' => 'publishing_target_id', 'source_column' => 'publishing_target_id'),
        "Solr Target" => array( 'target_name' => 'solr', 'source_column' => 'solr_target'),
    );

    function __construct($ss) {
        $this->sss = $ss; // fully built SharedShelfService object
    }

    function getSetSolrFields() {
        return $this->set_solr_fields;
    }

    function getCopyFields() {
        return $this->copy_fields;
    }

    function getCollectionFields() {
        return $this->collection_fields;
    }

    function set_project($project_id) {
        try {
            $this->project = $project_id;
            $this->metadata = $this->sss->project_metadata($this->project);
            $this->project_fields2();
        }
        catch (Exception $e) {
            echo 'set_project caught exception: ',  $e->getMessage(), "\n";
            throw $e;
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
            if (isset($this->map_fields2["$map_field_base"])) {
                if ($this->map_fields2["$map_field_base"]['multivalued'] && $map_field_no > 1) {
                    $col['order'] = $map_field_no;
                    $solr_field = $this->map_fields2["$map_field_base"]["solr_name"];
                    $solr_field = preg_replace('/_([^_]+)(_?.?+)/', '_${1}' . $map_field_no . '${2}', $solr_field);
                }
                else {
                    $solr_field = $this->map_fields2["$map_field_base"]["solr_name"];
                }
                $solr_base = $this->map_fields2["$map_field_base"]["solr_name"];
                // solr fields now have extensions already
                // $ext = $this->get_solr_extension($solr_base);
                // $solr_field = empty($ext) ? $solr_field : $solr_field . '_' . $ext;
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
            case 'project_id':          $extension = 'ssi'; break;
            case 'updated_on':          $extension = 'ss'; break;
            
            default:
                $extension = 'tesim';
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
            if (isset($this->map_fields2["$map_field_base"])) {
                $solr_field = $this->map_fields2["$map_field_base"]["solr_name"];
                if ($this->map_fields2["$map_field_base"]['multivalued']) {
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

    function getMAPFields() {
        return $this->map_fields2;
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
 
        // find collection level data in CSV file
        $collection = false;
        $csv = readCSV("collection_metadata.csv");
        foreach ($csv as $vals) {
            if (!(isset($vals['active']) && $vals['active'] == 'yes')) {
                continue;
            }
            if (isset($vals['collection_id']) && $vals['collection_id'] == $this->project) {
                $collection = $vals;
                break;
            }
        }
        if ($collection === false) {
            return '';
        }

        // be sure required fields are specified
        foreach ($collection as $key => $value) {
            switch ($key) {
                case 'active':
                case 'collection_id':
                case 'nickname':
                case 'collection_name':
                case 'collection_portal_path':
                case 'max_download_size':
                case 'format':
                case 'solr_target':
                case 'creator_sort':
                case 'title_sort':
                    if (empty($value)) {
                        throw new Exception("Missing value for $key", 1);                       
                    }
                    break;
                
                default:
                    # code...
                    break;
            }
        }
        $lines = array();
        $lines[] = ';; account configuration for ss2solr';
        $lines[] = 'solr = "' . $collection['solr_target'] . '"';
        $lines[] = ';; add the project ID from sharedshelf';
        $lines[] = 'project = "' . $this->project . '"';
        $lines[] = "";
        foreach($this->ss2map as $ssField => $info) {
            $lines[] = '; ' . $info['header'];
            $lines[] = 'fields[' . $ssField . '] = "' . $info['solr'] . '"';
            $lines[] = '';
        }

        $lines[] = '; special media field added by us';
        foreach ($this->collection_fields as $fld) {
            $lines[] = 'fields[' . $fld['field_name'] . '] = "'. $fld['solr_name'] . '"';
        }
        
        $lines[] = ';; copy ss fields to designated field names';
        $lines[] = ';; note: the left hand key here should match the right hand key above!!!!';
        $lines[] = ';; copy_field[source solr field] = "solr target field"';
        foreach ($this->copy_fields as $key => $value) {
            $lines[] = 'copy_field[' . $value['source_name'] . '] = "' . $value['target_name'] . "\"";
        }
        $lines[] = '';

        // max download size
        $lines[] = '';
        $lines[] = ';; if you want users to download full sized images, use media_URL_tesim';
        $lines[] = ';; otherwise use media_URL_size_4_tesim';
        $source_column = $this->set_solr_fields['Max Download']['source_column'];
        $source = $collection["$source_column"];
        $target = $this->set_solr_fields['Max Download']['target_name'];
        $lines[] = 'copy_field[' . $source . '] = "' . $target . "\"";
        $lines[] = '';

        foreach ($this->set_solr_fields as $key => $value) {
            $source_column = $value['source_column'];
            if (empty($collection["$source_column"])) {
                continue;
            }
            $source = $collection["$source_column"];
            if (empty($value['target_name'])) {
                if (empty($this->collection['$key'])) {
                    throw new Exception("Missing target for $key", 1);
                }
                else {
                    $target = $collection['$key'];
                }
            }
            else {
                $target = $value['target_name'];
            }
            switch ($key) {
                case 'Max Download':
                    continue;   // handled as copyfield
                    break;
                case 'Creator Sort':
                case 'Title Sort':
                    //single value
                    $lines[] = 'set_single_value[' . $target . '] = "' . $source . '"';
                    break;
                case 'Collection ID':
                case 'Solr Target':
                    continue; // special cases
                    break;
                case 'Location Type':
                    if (!empty($source)) {
                        $locs = explode('|', $source);
                        foreach ($locs as $location_type) {
                            switch ($variable) {
                                case 'geocoordinates':
                                    $line[] = 'set_location[where_geocoordinates] = "latitude_tsi,longitude_tsi"';
                                    break;
                                case 'where':
                                    $line[] = 'set_location[where_ssim] = "latitude_tsi,longitude_tsi"';
                                    break;
                                case 'geojson':
                                    $line[] = ';; fields that will end up in the geojson.';
                                    $line[] = ';; the fields need to be in this order: $lat,$lon,$loc,$id,$thumb';
                                    $line[] = ';; the third item, $loc, is whatever you want to be used as the placename in the popup';
                                    $line[] = ';; use whatever SSC image size you want for the last field $thumb, which becomes the thumbnail in the popup';                                    
                                    $line[] = 'set_geojson[geojson_ssim] = "latitude_tsi,longitude_tsi,title_tesim,id,media_URL_size_1_tesim"';
                                    break;
                                case 'located':
                                    $line[] = 'set_location[located_llsim] = "latitude_tsi,longitude_tsi"';
                                    break;
                                default:
                                    throw new Exception("Invalid location type", 1);
                                    break;
                            }
                        }
                    }
                    break;
                default:
                    $lines[] = 'set_solr_field[' . $target . '] = "' . $source . '"';
                    break;
            }
        }
        $lines[] = '';
        
        return implode("\n", $lines);
    }

    public function listFields() {
        // list all the solr fields
        $lines = array();
        foreach ($this->map_fields2 as $key => $value) {
            $solr_field = $value['solr_name'];
            $lines[] = $solr_field;
            $count = preg_match('/\_[a-z]+$/', $solr_field, $matches);
            $ext = $matches[0];
            $lines[] = $ext;
            $solr_prefix = substr($solr_field, 0, strlen($solr_field) - strlen($ext));
            //$ext = $this->get_solr_extension($solr_field);
            //$lines[] = empty($ext) ? $solr_field : $solr_field . '_' . $ext;
            if ($value['multivalued']) {
                for ($i = 2; $i <= 5; $i++) {
                    if (empty($ext)) {
                        $lines[] = $solr_field . $i;
                    }
                    else {
                        $lines[] = $solr_prefix . $i  . $ext;
                    }
                }
            }
        }

        foreach ($this->collection_fields as $key => $value) {
            $lines[] = $value['solr_name'];            
        }

        foreach ($this->copy_fields as $key => $value) {
            if (isset($value['target_field'])) {
                $lines[] = $value['target_field'];            
            }
        }

        foreach ($this->set_solr_fields as $key => $value) {
            if (isset($value['target_name'])) {
                $lines[] = $value['target_name'];            
            }
        }
        
        sort($lines);
        return $lines;
    }

    public function mapSolrFields() {
        $lines = array();
        foreach ($this->map_fields2 as $key => $value) {
            $parts = array();
            //$solr_field = preg_replace('/^map_/','',$value['solr_name']);
            $map_field = $value['map_name'];
            $ext = $this->get_solr_extension($value['solr_name']);
            $parts[] = '"' . $map_field . '"';
            //$parts['solr'] = empty($ext) ? $solr_field : $solr_field . '_' . $ext;
            $parts[] = $value['solr_name'];
            $parts[] = $value['multivalued'] ? 'multiple' : 'single';
            $lines[] = implode(',', $parts) . "\n";
        }      
        sort($lines);
        return $lines;
    }

}
?>