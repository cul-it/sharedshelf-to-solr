#!/bin/bash

mkdir -p /tmp/jgr25/convert-output

cd /tmp/jgr25/image-test

for f in *.tif
do convert $f /tmp/jgr25/convert-output/$f.png
done
