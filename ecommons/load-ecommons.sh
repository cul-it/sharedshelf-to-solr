#!/bin/bash 
# prior to this, run this:
# $> php generate-collection-list.php > collection-list.txt

PHP=`which php`

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# read each collection name and load the collection into solr
while read col; do
    "$PHP" "${DIR}/ecommons-to-solr.php" -c "$col" --force
done <"${DIR}/collection-list.txt"