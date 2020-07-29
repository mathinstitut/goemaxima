#!/bin/bash
# arg1: stack or moodle version: "stack-XXX" or "moodlev.X"
# arg2: REGISTRY or dockerhub id

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
echo "sbcl: $maximaver"
echo "maxima: $sbclver"
echo "stackmaxima: $stackver"
echo "goemaxima: $goemaxver"
REG="$2"
IMAGENAME="goemaxima:$1"
if [ -n "$REG" ]; then
	docker pull "$2/$IMAGENAME-dev"
fi
# build it
docker build -t "${IMAGENAME}-dev" --build-arg MAXIMA_VERSION="$maximaver" --build-arg SBCL_VERSION="$sbclver" --build-arg LIB_PATH="$libpath" . || exit 1
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
