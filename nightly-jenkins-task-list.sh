# commands for the nightly sharedshelf to solr run
# sharedshelf-to-solr.php [--help] [--force] [--no-write] [--use-dev-solr] [--skip] [-p NNN] [-s NNN] [-n NNN]
# projects (-p):
# see sharedshelf-to-solr.ini projects - commented out ones are NOT in production - use --skip flag
# 108 - Cornell Coins Collection - asset count: 1804
# 111 - Divine Comedy - asset count: 1898
# 112 - Howell Icelandic - asset count: 416
# 134 - Vicos Collection - asset count: 2246
# 135 - Billie Jean Isbell - asset count: 1190
# 139 - Knowledge of the World in Early Modern Japan - asset count: 4231
# 14054 - Fallout Pamphlet Collection - asset count: 15
# 141 - CUL Map Collection - asset count: 1531
# 143 - Bernard Kassoy Teachers News Cartoons - asset count: 1190
# 155 - Reuleaux Kinematic Mechanisms Collection - asset count: 223
# 166 - Mysteries at Eleusis - asset count: 139
# 167 - Hip Hop Flyers - asset count: 494
# 174 - Images from the Rare Book and Manuscript Collections - asset count: 17232
# 190 - Joe Conzo Jr. Archive - asset count: 7620
# 1146 - Sri Lankan Vernacular - asset count: 537
# 256 - Obama Visual Iconography - asset count: 200
# 273 - Selections from the Cornell Anthropology Collections - asset count: 2392
# 2895 - The J. R. Sitlington Sterrett Collection of Archaeological Photographs - asset count: 545
# 20019 - Impersonator Cards - asset count: 884
# 28160 - Tom Harrisson Photographs - asset count: 277
# 319 - Loewentheil African American Photographs - asset count: 1482
# 3262 - Charlie Ahearn Archive - asset count: 909
# 3321 - Test Project - asset count: 12
# 3450 - IWO/JPFO - asset count:
# 3462 - Punk Flyers - asset count: 95
# 3596 - U.S. President's Railroad Commission Photographs - asset count: 1654
# 3609 - John Clair Miller - Contemporary Icelandic Architecture - asset count: 615
# 3686 - Digitizing Tell en-Naṣbeh, Biblical Mizpah of Benjamin - asset count: 50
# 370 - John Reps Slides - asset count: 1357
# 37250 - Afrika Bambaataa Hip Hop Archive - asset count: 2108
# 3786 - Blaschka Glass Invertibrate Models - asset count: 50
# 4210 - Eugene B. Dynkin Collection of Mathematics Interviews - asset count: 916
# 4406 - 19th Century Prison Reform Collection - asset count: 487
# 4409 - Rudin Antislavery Collection - asset count: 513
# 4411 - Lindsay Cooper Digital Archive - asset count: 589
# 4497 - Art 2301 Printmaking Student Portfolios - asset count: 29
# 452 - Cornell Gems Collection - asset count: 3794
# 4547 - NYS Historical Dendrochronology Project - asset count: 1139
# 48 - Campus Artifacts, Art &amp; Memorabilia - asset count: 1673
# 4803 - Seneca Haudenosaunee Archaeological Materials, circa 1688-1754 - asset count: 1183
# 49 - Kroch Asia Rare Materials Archive - asset count: 3782
# 50 - Theatre Prints and Books from Early Modern Japan - asset count: 1076
# 522 - Tamang - asset count: 2539
# 531 - Historic Glacial Images of Alaska and Greenland (Tarr) - asset count: 2136
# 559 - Squeezes - asset count: 369
# 589 - Reps Bastides - asset count: 2652
# 616 - Gamelan - asset count: 565
# 659 - PJ Mode Map Collection - asset count: 310
# 685 - Andrew Dickson White Architectural Photographs Collection - asset count: 1364
# 686 - Hill  Ornithology Collection - asset count: 202
# 657 - John Clair Miller - asset count: 240
# 687 - Beyond the Taj: Architectural Traditions and Landscape Experience in South Asia - asset count: 6688
# 746 - Ragamala Paintings - asset count: 4123
# 757 - Ernie Paniccioli - asset count: 5688
# 78 - NYS Aerial Photographs - asset count: 3390
# 88 - Alison Mason Kinsbury - asset count: 522
# 89 - Willard D. Straight in Korea - asset count: 176
# 893 - Bill Adler Archive - asset count: 2432
# 920 - Efraim Racker Art Albums - asset count: 81
# 922 - Political Americana - asset count: 3899
# 954 - Leuenberger Map Collection - asset count: 230
# 962 - Icelandic Stereoscopes - asset count: 234
# 97 - Cornell Cast Collection - asset count: 897
# 98 - Claire Holt Papers - asset count: 1785

PHP='/usr/bin/php72'

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 108 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 111 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 112 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 134 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 135 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 139 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 141 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 143 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 155 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 166 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 167 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 174 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 190 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 1146 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 14054 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 256 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 273 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 2895 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 20019 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 28160 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 319 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3262 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3450 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3462 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3596 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3609 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3686 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 370 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 37250 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 3786 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4210 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4406 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4409 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4411 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4497 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 452 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4547 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 48 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 4803 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 49 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 50 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 522 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 531 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 559 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 589 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 616 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 657 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 659 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 685 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 686 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 687 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 746 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 757 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 78 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 88 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 89 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 893 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 920 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 922 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 954 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 962 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 97 &
"$PHP" "${DIR}/sharedshelf-to-solr.php" -p 98 &


# example of creating or updating the IIIF images for a Collection
#"$PHP" "${DIR}/sharedshelf-to-iiif-s3.php" -p 962 &


# waiting until all the spawned processes finish up
wait