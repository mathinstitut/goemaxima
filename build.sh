#!/bin/sh
# This script runs buildimage.sh for each combination STACK/ of versions listed in the 'versions' file.
#
# Run with two arguments:
# arg1: REGISTRY or dockerhub id to use. If given, teh built container is pushed there with tag -dev.
# arg2: goemaxima version number, used to tag the containers that are built. If there is a dash, then
#       with a tag like 2022071300-1.1.5, only the container for stackmaxima version 2022071300 would
#       be built and tagged with 2022071300-1.1.5 and 2022071300-latest

REGISTRY=$1
# note that cut by default outputs the whole line if there is no delimiter
goemaxima_version="$(echo "$2" | cut -f2 -d-)"
stack_versions="$(echo "$2" | cut -f1 -d- -s)"
if [ -z "$stack_versions" ]; then
	stack_versions="$(grep -v '^#' versions | cut -f1)"
fi
for ver in $stack_versions; do
	/bin/sh buildimage.sh "$ver" "${REGISTRY}" "$goemaxima_version" || exit 1
done
