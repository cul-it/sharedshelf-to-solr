#!/bin/bash
if [ $# -lt 1 ]
then
 echo "useage: $0 <Forum asset id number>"
 exit 1
fi

# have to use internal. if running on vpn instead of lamp-legacy
HOST=`hostname`
if [[ "$HOST" == *"serverfarm"* ]]; then
  INTERNAL=""
else
  INTERNAL="internal."
fi

ASSET=$1
FORUM=`php ssAssetTest.php -s $ASSET`
PROJECT=`echo $FORUM | grep -E "\[project_id[^\[]+" -o | grep -E "[0-9]+" -o`
FILENAME=`echo $FORUM | grep -E "\[filename[^\[]+" -o | cut -d ' ' -f 3`
FORUMDATE=`echo $FORUM | grep -E "\[updated_on[^\[\)]+" -o | cut -d ' ' -f 3`
SOLRPRODDATE=`curl -s "http://digcoll.${INTERNAL}library.cornell.edu/solr/digitalcollections2/select?q=id%3Ass\%3A$ASSET&fl=timestamp&wt=json&indent=true" | grep -E "\"timestamp\":\"[^\}]+" -o | cut -d '"' -f 4`
SOLRPDEVDATE=`curl -s "http://digcoll-dev.${INTERNAL}library.cornell.edu:8983/solr/digitalcollections2/select?q=id%3Ass\%3A$ASSET&fl=timestamp&wt=json&indent=true" | grep -E "\"timestamp\":\"[^\}]+" -o | cut -d '"' -f 4`
PDFDATE=`aws s3 ls sharedshelftosolr.library.cornell.edu/public/$PROJECT/$FILENAME | cut -d ' ' -f 1,2`
IIIFDATE=`aws s3 ls sharedshelftosolr.library.cornell.edu/public/$PROJECT/$ASSET/image/info.json | cut -d ' ' -f 1,2`
printf "%34s%30s\n" "Asset ID:" $ASSET
printf "%34s%30s\n" "Project ID:" $PROJECT
printf "%34s%30s\n" "Original Filename:" "$FILENAME"
printf "%34s%30s\n" "Last updated in Forum:" "$FORUMDATE"
printf "%34s%30s\n" "Last updated in solr production:" "$SOLRPRODDATE"
printf "%34s%30s\n" "Last updated in solr dev:" "$SOLRPDEVDATE"
printf "%34s%30s\n" "Date of PDF in S3:" "$PDFDATE"
printf "%34s%30s\n" "Date of IIIF in S3:" "$IIIFDATE"
