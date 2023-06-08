#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#first argument is explicit version
VERSION=$1
if [ -n "$VERSION" ]; then
  ver-bump -v $VERSION -p origin
else
  ver-bump -p origin
fi
git checkout development
last_branch=$(git branch --contains `git rev-list --tags --max-count=1` release*)
outputMessage "H" "merging development branch with ${last_branch}";
git merge $last_branch
git push --set-upstream origin development
