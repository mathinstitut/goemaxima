REGISTRY=$1
grep -v '^#' versions | cut -f1 | \
while read -r ver; do
	goemaxima_version="$2"
	bash ./buildimage.sh "$ver" "${REGISTRY}" "$goemaxima_version" || exit 1
done
