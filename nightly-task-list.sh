# commands for the nightly sharedshelf to solr run
# sharedshelf-to-solr.php [--help] [--force] [--no-write] [-p NNN] [-s NNN] [-n NNN]
# projects (-p):
# 48 - Campus Artifacts, Art &amp; Memorabilia
# 78 - NYS Aerial Photographs
# 370 - Reps Slides
# 522 - Tamang
# 589 - Reps Bastides
# 616 - Gamelan
# 659 - PJ Mode Map Collection
# 687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia
# 746 - Ragamala Paintings
# 1146 - Sri Lankan Vernacular

"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 48 --no-write
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 78 --no-write
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 370 --no-write
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 522
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 589
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 616
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 659
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 687
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 746
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 1146
