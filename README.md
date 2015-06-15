# ss2solr - migrate metadata from sharedshelf to solr

Run:
- php sharedshelf-to-solr.php

Installation:
- check out from git
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
