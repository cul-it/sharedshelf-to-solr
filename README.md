# ss2solr - migrate metadata from sharedshelf to solr

Installation:
- check out from git
- add a text file called ssUser.ini to the directory with THIS file in it

contents of ssUser.ini (your sharedshelf user name (email) and password):

    ;; account configuration for sharedshelf user
    email = jgr25@cornell.edu
    password = thisisnotreallymypassword


Note: I don't deal with fields Sharedshelf returns as arrays

Fields to add:
- Collection_s = name of the ss collection (eg. NYS Aerial Photographs, Reps Slides)
- Title_t = generic title of document
- Image_Type_s = one of (video,audio,image)

Fields to add for spotlight:
- full_title_tesim  (title)
- spotlight_upload_description_tesim (description)
- spotlight_upload_attribution_tesim (rights)
- spotlight_upload_date_tesim (you could leave this blank or make it equal to the date it got added to shared shelf)
