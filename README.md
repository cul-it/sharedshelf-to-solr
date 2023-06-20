# ss2solr - migrate metadata from sharedshelf to solr

## Obsolete
- replaced by digcoll-solr-ingest

## What it does:
- moves metadata from sharedshelf into a solr index
- provides a mechanism for using a sharedshelf user's credentials to access the sharedshelf API
 - see notes below about ssUser.ini
- provides a mechanism for allowing users to map sharedshelf fields into solr fields
  - see notes in ss2solr.example.ini
  - see list of collections to map in sharedshelf-to-solr.ini
- converts images stored in sharedshelf into images in IIIF format stored in Amazon S3
 - see shareshelf-to-iiif-s3.php
- helper scripts
  - iiif-check.sh - see if the file has already been converted to iiif
  - listFields.php - writes out a list of fields for use in collection .ini file
  - listProjects.php - writes out projects and project ids
  - listPublicationTargets.php - list the id of the publication targets for each project
  - nightly-checkout-master.sh - cron job that runs BEFORE nightly.sh to pull down the latest master branch
  - nightly.sh - cron job that runs the commands in nightly-task-list.sh
  - sharedshelf-status.php - determine if sharedshelf items have been converted to solr and iiif (not working)
  - spot-check.sh - check the timestamps for an asset in Forum, solr, and IIIF or PHP on AWS S3
  - ssAssetTest.php - list all the fields returned from Forum's API for an asset
  - ssGeoTagExtract.php - Grab Geotags from sharedshelf image

## Run:
- php sharedshelf-to-solr.php --help

  <pre>
  Usage: php sharedshelf-to-solr.php [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]
  --help - show this info
  --force - ignore timestamps and rewrite all solr records
  --no-write - do everything EXCEPT writing the solr records
  --use-dev-solr - override the solr core specified in .ini file using http://jrc88.solr.library.cornell.edu/solr/digitalcollections_dev
  --skip - do not process this collection (only when -p is specified)
  -p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php
  -s - start processing at the given SharedShelf asset number NNN (NNN must be numeric) (asset numbers ascend during processing)
  -n - process only this many (integer) assets
</pre>

sharedshelf-to-solr.php without arguments:
- moves metadata for all collections listed in sharedshelf-to-solr.ini into solr
- checks the timestamps of the assets already in solr and only moves the ones that have been updated in sharedshelf since
- we run it this way nightly to keep the solr index fresh

- php sharedshelf-to-iiif-s3.php --help

  <pre>
    Usage: php sharedshelf-to-iiif-s3.php [--help] [--force] [-p NNN] [-s NNN]
    --help - show this info
    --force - ignore timestamps and rewrite all solr records
    -p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php
    -s - only process one of the images in the collection - id NNN
    --startdate yyyy-mm-dd - process only Forum assets with updated_on this date or later
    --enddate yyyy-mm-dd - process only Forum assets with updated_on this date or earlier
  </pre>

- sharedshelf-to-iiif-s3.php creates a static IIIF file for each image, stored on AWS S3
- use --startdate and --enddate in combination to select a range - or use just one

## Installation:
- check out from git
- install s3cmd for using Amazon S3 - http://s3tools.org/s3cmd
- someone needs an account on sharedshelf that has access to the collections
- make a description of each collection in ss2solr.<collection>.ini
- add a text file called ssUser.ini to the directory with THIS file in it

contents of ssUser.ini (your sharedshelf user name (email) and password):

    ;; account configuration for sharedshelf user
    email = bozo@cornell.edu
    password = thisisnotreallymypassword

For the CSV stuff:
- composer require league/csv
- composer update

## .ini files
- one for each collection
- named like ss2solr.aerial.ini where aerial is the collection
- fields[fd_1945_s] = "title_tesim"
 - fd_1945_s is the name of the field in sharedshelf and title_tesim is the name in of the solr field fd_145_s maps to
- copy_field[title_tesim] = "full_title_tesim"
 - title_tesim is one of the solr fields from the right hand side of a fields[] declaration
 - full_title_tesim is a second, new solr field that gets a copy of the value from the first field for each record
- set_solr_field[collection_tesim] = "New York State Aerial Photographs"
 - collection_tesim is a new solr field that gets set to the given value for each and every record in the collection


## Notes

When Sharedshelf returns arrays I flatten them with impolode('; ', $junk)

Fields to add:
- Collection_s = name of the ss collection (eg. NYS Aerial Photographs, Reps Slides)
- Title_t = generic title of document
- Image_Type_s = one of (video,audio,image)

Fields to add for spotlight:
- full_title_tesim  (title)
- spotlight_upload_description_tesim (description)
- spotlight_upload_attribution_tesim (rights)
- spotlight_upload_date_tesim (you could leave this blank or make it equal to the date it got added to shared shelf)

see notes about .ini files for each collection in the template ss2solr.example.ini
