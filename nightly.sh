#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

PHP=`which php`
GIT=`which git`

# check out the latest master branch
"$GIT" checkout master
"$GIT" pull

# run the task list
. ./nightly-task-list.sh
