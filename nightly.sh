#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

PHP=`which php`
GIT=`which git`

# check out the latest master branch
"$GIT" checkout master
"$GIT" pull

# pull in the prarameter file
# nightly-tonight-only.sh looks like this:
#
# #!/bin/bash
# FORCE_UPDATE=1

if [ -f ./nightly-tonight-only.sh ]; then
  . ./nightly-tonight-only.sh
# the file gets deleted after a singe use!!!
  rm nightly-tonight-only.sh
fi

# check out the latest master branch
"$GIT" checkout master
"$GIT" pull

if [[ "$FORCE_UPDATE" ]]; then
  echo "$PHP" "${DIR}/sharedshelf-to-solr.php --force"
else
  echo "$PHP" "${DIR}/sharedshelf-to-solr.php"
fi
