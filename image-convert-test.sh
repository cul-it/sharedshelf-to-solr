#!/bin/bash

mkdir -p /tmp/jgr25/convert-output

cd /tmp/jgr25/RAC_017

for f in *.tif
do convert $f /tmp/jgr25/convert-output/$f.jpg
done

