REGISTRY=$1
grep -v '^#' versions | \
while read -r line; do
	maxima_version="$(echo "$line" | cut -f1)"
	sbcl_version="$(echo "$line" | cut -f2)"
	stack_version="$(echo "$line" | cut -f3)"
	./buildimage.sh "${sbcl_version}" "${maxima_version}" "$stack_version" "stack/$stack_version/maxima" "${REGISTRY}" || exit 1
done
