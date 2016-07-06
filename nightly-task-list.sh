# commands for the nightly sharedshelf to solr run
# sharedshelf-to-solr.php [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]
# projects (-p):
# see sharedshelf-to-solr.ini projects - commented out ones are NOT in production - use --skip flag
# 108 - Cornell Coins Collection - asset count: 1804
# 112 - Howell Icelandic - asset count: 416
# 135 - Billie Jean Isbell - asset count: 1190
# 166 - Mysteries at Eleusis - asset count: 847
# 167 - Hip Hop Flyers - asset count: 494
# 190 - Joe Conzo Jr. Archive - asset count: 7620
# 319 - Loewentheil African American Photographs - asset count: 1482
# 370 - John Reps Slides - asset count: 1357
# 48 - Campus Artifacts, Art &amp; Memorabilia - asset count: 1673
# 522 - Tamang - asset count: 2539
# 531 - Historic Glacial Images of Alaska and Greenland (Tarr) - asset count: 2136
# 559 - Squeezes - asset count: 369
# 589 - Reps Bastides - asset count: 2652
# 616 - Gamelan - asset count: 565
# 659 - PJ Mode Map Collection - asset count: 310
# 685 - Andrew Dickson White Architectural Photographs Collection - asset count: 1364
# 657 - John Clair Miller - asset count: 240
# 687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia - asset count: 6688
# 746 - Ragamala Paintings - asset count: 4123
# 78 - NYS Aerial Photographs - asset count: 3390
# 88 - Alison Mason Kinsbury - asset count: 522
# 920 - Efraim Racker Art Albums - asset count: 81
# 97 - Cornell Cast Collection - asset count: 897

PHP=`which php`

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 108
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 112
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 135
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 166
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 167
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 190
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 319
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 370
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 48
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 522
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 531
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 559 --force
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 589
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 616
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 657
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 659
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 685
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 687
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 746
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 78
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 88
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 920
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 97

# example of creating or updating the IIIF images for a Collection
"$PHP" "${DIR}/sharedshelf-to-iiif-s3.php" -p 319