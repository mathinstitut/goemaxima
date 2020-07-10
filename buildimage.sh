##/bin/bash
# arg1: sbcl version
# arg2: maxima version
# arg3: stack or moodle version: "stack-XXX" or "moodlev.X"
# arg4: LIB_PATH
# arg5: REGISTRY IP
# arg6: version of goemaxima
#
echo "starting to build image for:"
echo "sbcl: $1"
echo "maxima: $2"
echo "stack: $3"
# tag the image
IMAGENAME="$5/goemaxima-$3:$6"
IMAGELATEST="$5/goemaxima-$3:latest"
# check if the image already exists on the server
docker pull "${IMAGENAME}"
# build it
if [ "$3" = "2017121800" ]; then
	docker build -t "${IMAGENAME}" --build-arg MAXIMA_VERSION="$2" --build-arg SBCL_VERSION="$1" --build-arg LIB_PATH="$4" --build-arg "MAX_LIB_PATH=/opt/maxima/assets/maximalocal.mac" . || exit 1
else
	docker build -t "${IMAGENAME}" --build-arg MAXIMA_VERSION="$2" --build-arg SBCL_VERSION="$1" --build-arg LIB_PATH="$4" . || exit 1
fi
echo "${IMAGENAME} wurde erfolgreich gebaut."
# push it
docker push "${IMAGENAME}"

