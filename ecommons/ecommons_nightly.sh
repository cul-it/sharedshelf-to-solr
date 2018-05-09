#!/bin/bash

# find the directory this script is in
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# run the task list
. "${DIR}/nightly-task-list.sh"
