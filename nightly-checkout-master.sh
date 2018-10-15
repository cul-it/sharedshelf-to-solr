#!/bin/bash
# run this in cron before nightly.sh to insure we have the latest master branch to work with

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "${DIR}"

GIT=`which git`

# check out the latest master branch
"$GIT" --git-dir="${DIR}/.git" --work-tree="${DIR}" checkout digcoll_nonmap
"$GIT" --git-dir="${DIR}/.git" --work-tree="${DIR}" pull origin digcoll_nonmap
