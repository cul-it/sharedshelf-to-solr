# ss2solr - migrate metadata from sharedshelf to solr

version: beta17

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
  - listFields.php - writes out a list of fields for use in collection .ini file
  - listProjects.php - writes out projects and project ids
  - iiif-check.sh - see if the file has already been converted to iiif
  - listPublicationTargets.php - list the id of the publication targets for each project
  - sharedshelf-status.php - determine if sharedshelf items have been converted to solr and iiif
  - ssGeoTagExtract.php - Grab Geotags from sharedshelf image

## Run:
- php sharedshelf-to-solr.php --help

  Usage: php sharedshelf-to-solr.php [--help] [--force] [-p NNN] [-s NNN] [-n NNN]
  --help - show this info
  --force - ignore timestamps and rewrite all solr records
  --no-write - do everything EXCEPT writing the solr records
  -p - only process SharedShelf collection (project number) NNN (NNN must be numeric) - see listProjects.php
  -s - start processing at the given SharedShelf asset number NNN (NNN must be numeric) (asset numbers ascend during processing)
  -n - process only this many (integer) assets

sharedshelf-to-solr.php without arguments:
- moves metadata for all collections listed in sharedshelf-to-solr.ini into solr
- checks the timestamps of the assets already in solr and only moves the ones that have been updated in sharedshelf since
- we run it this way nightly to keep the solr index fresh

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


Note: When Sharedshelf returns arrays I flatten them with impolode('; ', $junk)

Fields to add:
- Collection_s = name of the ss collection (eg. NYS Aerial Photographs, Reps Slides)
- Title_t = generic title of document
- Image_Type_s = one of (video,audio,image)

Fields to add for spotlight:
- full_title_tesim  (title)
- spotlight_upload_description_tesim (description)
- spotlight_upload_attribution_tesim (rights)
- spotlight_upload_date_tesim (you could leave this blank or make it equal to the date it got added to shared shelf)

see notes about .ini files for each collection in the template ss2solr.ini

listFields.php - writes out a list of fields for use in collection .ini file

listProjects.php - writes out projects and project ids
