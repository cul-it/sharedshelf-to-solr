#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

PHP=`which php`
GIT=`which git`

# check out the latest master branch
"$GIT" checkout master
"$GIT" pull

# pull in the prarameter file
# nightly-use-force.sh looks like this:
#
# #!/bin/bash
# FORCE_UPDATE=1

if [ -f ./nightly-use-force.sh ]; then
  . ./nightly-use-force.sh
# the file gets deleted after a singe use!!!
  echo "Removing $DIR/nightly-use-force.sh"
  rm nightly-use-force.sh
fi

if [[ "$FORCE_UPDATE" ]]; then
  echo "Running sharedshelf-to-solr.php with the --force option"
  "$PHP" "${DIR}/sharedshelf-to-solr.php" --force
else
  "$PHP" "${DIR}/sharedshelf-to-solr.php"
fi
