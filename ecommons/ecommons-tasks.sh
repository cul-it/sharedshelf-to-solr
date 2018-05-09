#!/bin/bash 

PHP=`which php`

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# create a new list of eCommons collections
"$PHP" "${DIR}/generate-collection-list.php" > "${DIR}/collection-list.txt"

# load each collection into solr
. "${DIR}/load-ecommons.sh"

