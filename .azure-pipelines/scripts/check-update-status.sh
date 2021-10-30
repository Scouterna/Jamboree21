#!/bin/bash -e

target="$1"
subpath="$2"

if [[ "$target" == "" ]] || [[ "$subpath" == "" ]]; then
	echo "Usage: ./$0 [<build target> [<subpath>]]"
	exit 1
fi

tag="$(git tag | grep build-"$target"- | tail -n 1 | tr -d '\n')"

updated='false'

if [[ "$tag" == "" ]] || [[ "$(git log "$tag"..HEAD --format=%h -- "$subpath" | tr -d '\n')" != "" ]]; then
	updated='true'
	echo "Updates to $target since the last build:"
	git log "$tag"..HEAD --format=oneline -- "$subpath"
fi


echo "The $target project is $([[ "$updated" == 'true' ]] && echo 'updated' || echo 'not updated') since the last build."

for setter in "##vso[task.setvariable variable=updated]$updated" "##vso[task.setvariable variable=updated;isOutput=true]$updated"; do
	echo "$setter"
	echo "'$setter"
done
