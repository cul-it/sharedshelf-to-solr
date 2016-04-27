# commands for the nightly sharedshelf to solr run
# sharedshelf-to-solr.php [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]
# projects (-p):
# see sharedshelf-to-solr.ini projects - commented out ones are NOT in production - use --skip flag
# 167 - Hip Hop Flyers - asset count: 494
# 370 - Reps Slides - asset count: 1357
# 48 - Campus Artifacts, Art &amp; Memorabilia - asset count: 1673
# 522 - Tamang - asset count: 2539
# 589 - Reps Bastides - asset count: 2652
# 616 - Gamelan - asset count: 565
# 659 - PJ Mode Map Collection - asset count: 310
# 687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia - asset count: 6688
# 746 - Ragamala Paintings - asset count: 4123
# 78 - NYS Aerial Photographs - asset count: 3390

PHP=`which php`

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 167
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 370 --skip
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 48
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 522 --skip
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 589
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 616
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 659
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 687
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 746
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 78

# example of creating or updating the IIIF images for a Collection
# "$PHP" "${DIR}/sharedshelf-to-iiif-s3.php" -p 48