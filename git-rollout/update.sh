#!/bin/sh
SLEEP_TIME=180
DIR_NAME="git"
rm -rf "$DIR_NAME"
git clone "$1" "$DIR_NAME"
# required for git daemon to work on this repo
touch "$DIR_NAME/.git/git-daemon-export-ok"
git daemon --base-path="$(pwd)" --log-destination=stderr &
cd "$DIR_NAME"
while true; do
	git remote update
	# check whether there has been any update
	if [ "$(git rev-parse @)" = "$(git rev-parse '@{u}')" ]; then
		# do nothing if no update
		sleep "$SLEEP_TIME"
		continue
	fi
	# checkout latest commit
	git reset --hard origin
	# names of all deployments that match the labels in $2
	deployments="$(kubectl get deployment -l "$2" -o json | jq -r '.items[].metadata.name')"
	# restart all those deployments
	for deployment in $deployments; do
		kubectl rollout restart "deployment/$deployment"
	done
	sleep "$SLEEP_TIME"
done
