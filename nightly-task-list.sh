# commands for the nightly sharedshelf to solr run
# sharedshelf-to-solr.php [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]
# projects (-p):
# x means in production (see sharedshelf-status.ini)
# x 48 - Campus Artifacts, Art &amp; Memorabilia - asset count: 1673
# 370 - Reps Slides - asset count: 1357
# x 522 - Tamang - asset count: 2539
# x 589 - Reps Bastides - asset count: 2652
# x 616 - Gamelan - asset count: 565
# x 746 - Ragamala Paintings - asset count: 4123
# x 78 - NYS Aerial Photographs - asset count: 3390
# x 659 - PJ Mode Map Collection - asset count: 310
# x 687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia - asset count: 6688

"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 659
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 616
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 48
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 78
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 522
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 589
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 687 --force
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 746
