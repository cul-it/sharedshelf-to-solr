#!/bin/bash
# iiif-check.sh - check presence of the info.json file for s3://sharedshelftosolr.library.cornell.edu/public/ projects

S3BAS=s3://sharedshelftosolr.library.cornell.edu/public/

PROJECTS=`s3cmd ls s3://sharedshelftosolr.library.cornell.edu/public/ | rev | cut -d'/' -f 2 | rev`

echo $PROJECTS

for project in $PROJECTS
do
  ASSETS=`s3cmd ls s3://sharedshelftosolr.library.cornell.edu/public/$project/ | rev | cut -d'/' -f 2 | rev`
  echo "Project: $project"
  for asset in $ASSETS
  do
    INFO=`s3cmd ls s3://sharedshelftosolr.library.cornell.edu/public/$project/$asset/image/info.json | rev | cut -d'/' -f 1 | rev`
    if [ -z "$INFO" ]
      then
        echo "missing project $project asset $asset"
      fi
  done
done
