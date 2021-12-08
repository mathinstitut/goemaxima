#!/bin/bash
# This script builds the Docker container for a particular version of STACK.
#
# It should be run with one, two or three arguments:
# arg1: the vesrion of the stackmaxima code. This is looked up in the 'versions' file
#       to find the corresponding SBCL and Maxima versions to use.
# arg2: (optional) REGISTRY or dockerhub id to use. If given, teh built container is pushed there with tag -dev.
# arg3: (optional) if given, the built image is also pushed with this tag, and also -latest.

stackver="$1"
if [ -z "$stackver" ]; then
	echo "Stack version is missing"
	echo "Usage: $0 stackmaximaversion [registry] [containerversion]"
	exit 1
fi
verstring=$(awk '$1 == "'"$1"'"{ print $0 }' versions)
maximaver="$(echo "$verstring" | cut -f2)"
sbclver="$(echo "$verstring" | cut -f3)"
goemaxver="$(cat goemaxima_version)"
libpath="stack/$stackver/maxima"
echo "starting to build image for:"
echo "Maxima: $maximaver"
echo "SBCL: $sbclver"
echo "stackmaxima: $stackver"
echo "goemaxima: $goemaxver"

REG="$2"
IMAGENAME="goemaxima:$1"
#if [ -n "$REG" ]; then
#	docker pull "$2/$IMAGENAME-dev"
#fi

# build it
DOCKER_BUILDKIT=1 docker build -t "${IMAGENAME}-dev" --build-arg MAXIMA_VERSION="$maximaver" --build-arg SBCL_VERSION="$sbclver" --build-arg LIB_PATH="$libpath" . || exit 1
echo "${IMAGENAME} was built successfully."

# push the image
if [ -n "$REG" ]; then
	docker tag "$IMAGENAME-dev" "$2/$IMAGENAME-dev"
	docker push "$2/$IMAGENAME-dev"
	if [ -n "$3" ]; then
		docker tag "$IMAGENAME-dev" "$2/$IMAGENAME-$3"
		docker push "$2/$IMAGENAME-$3"
		docker tag "$IMAGENAME-dev" "$2/$IMAGENAME-latest"
		docker push "$2/$IMAGENAME-latest"
	fi
fi
