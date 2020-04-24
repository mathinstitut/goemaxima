#/bin/bash
REGISTRY=$1

for sbcl_version in $(cat sbcl_version); do
for maxima_version in $(cat maxima_version); do
for stack_version in $(cat stack_version); do
IFS=",";
set ${stack_version};
# checkout repository
cd assStackQuestion;
#git checkout $2
cd ../
./buildimage.sh ${sbcl_version} ${maxima_version} $1 "assStackQuestion/classes/stack/maxima" ${REGISTRY} || exit 1
unset IFS
done
#for moodle_version in $(cat moodle_version); do
#cd moodle-qtype_stack
#git checkout ${moodle_version}
#cd ../
#echo "starting to build image for:"
#echo "sbcl: "${sbcl_version}
#echo "maxima: "${maxima_version}
#echo "moodle: "${moodle_version}
#done
done
done
