#!/bin/sh
# This script runs buildimage.sh for each combination STACK/ of versions listed in the 'versions' file.
#
# Run with two arguments:
# arg1: REGISTRY or dockerhub id to use. If given, teh built container is pushed there with tag -dev.
# arg2: goemaxima version number, used to tag the containers that are built.

REGISTRY=$1
grep -v '^#' versions | cut -f1 | \
while read -r ver; do
	goemaxima_version="$2"
	/bin/sh buildimage.sh "$ver" "${REGISTRY}" "$goemaxima_version" || exit 1
done
