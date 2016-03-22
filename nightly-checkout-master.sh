#!/bin/bash
# run this in cron before nightly.sh to insure we have the latest master branch to work with

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "${DIR}"

GIT=`which git`

# check out the latest master branch
"$GIT" --git-dir="${DIR}/.git" --work-tree="${DIR}" checkout feature/test_pull_of_master_branch_on_nightly_cron
"$GIT" --git-dir="${DIR}/.git" --work-tree="${DIR}" pull origin feature/test_pull_of_master_branch_on_nightly_cron
