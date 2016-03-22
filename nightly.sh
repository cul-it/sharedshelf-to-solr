#!/bin/bash

# if the user supplies a -t argument, the current branch is used instead of the master branch
USE_MASTER_BRANCH=1
while getopts ":t" opt; do
  case $opt in
    t)
      echo "-t was triggered! using current branch" >&2
      USE_MASTER_BRANCH=
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 0
      ;;
  esac
done

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"
echo "$DIR"

PHP=`which php`
GIT=`which git`

if [[ "$USE_MASTER_BRANCH" == 1 ]]; then
  # check out the latest master branch
  "$GIT" checkout master
  "$GIT" pull
else
  # pull the latest changes on the current branch
  "$GIT" pull
fi

# run the task list
#. ./nightly-task-list.sh

# test comment to be pulled from git on the next cron run with -t option
