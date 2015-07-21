#!/bin/bash

# generates static images to support iiif

[ $# -eq 2 ] || echo "$0 needs 2 arguments: image url and output path" && exit 1

IMAGE_URL="$1"
OUTPUT_PATH="$2"
SCRIPT="/cul/share/iiif/iiif/iiif_static.py"

[ -f "$SCRIPT" ] || echo "$SCRIPT must exist" && exit 2

[ -d "$OUTPUT_PATH" ] || rm -r "$OUTPUT_PATH"
mkdir -p "$OUTPUT_PATH"

$CMD="python $SCRIPT --dst=$OUTPUT_PATH $IMAGE_URL"
echo $CMD


