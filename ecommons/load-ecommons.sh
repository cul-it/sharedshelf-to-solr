#!/bin/bash
# prior to this, run this:
# $> php generate-collection-list.php > collection-list.txt

while read col; do
    php ecommons-to-solr.php -c "$col"
done <collection-list.txt