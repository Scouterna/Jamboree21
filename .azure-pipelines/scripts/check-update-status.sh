#!/bin/bash -e

target="$1"
subpath="$2"

if [[ "$target" == "" ]] || [[ "$subpath" == "" ]]; then
	echo "Usage: ./$0 [<build target> [<subpath>]]"
	exit 1
fi

updated='true'

for setter in "##vso[task.setvariable variable=updated]$updated" "##vso[task.setvariable variable=updated;isOutput=true]$updated"; do
	echo "$setter"
	echo "'$setter"
done
