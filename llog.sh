#!/bin/bash
if [ $# -lt 1 ]
then
 echo "useage: $0 <directory where .log files are>"
 exit 1
fi
path=`ls -rt1 "$1"*.log | tail -n 1`
tail "$path"
echo "$path"
