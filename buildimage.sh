#/bin/bash
# arg1: sbcl version
# arg2: maxima version
# arg3: stack or moodle version: "stack-XXX" or "moodlev.X"
# arg4: LIB_PATH
# arg5: REGISTRY IP
#
echo "starting to build image for:"
echo "sbcl: "$1
echo "maxima: "$2
echo $3
# tag the image
IMAGENAME=$5"/sbcl"$1"_maxima"$2"_"$3
# check if the image already exists on the server
docker pull ${IMAGENAME}
# build it
docker build -t ${IMAGENAME} --build-arg MAXIMA_VERSION=$2 --build-arg SBCL_VERSION=$1 --build-arg LIB_PATH=$4 .
echo ${IMAGENAME}" wurde erfolgreich gebaut."
# testing?
# push it
docker push ${IMAGENAME}

