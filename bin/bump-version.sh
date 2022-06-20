#!/bin/bash
#first argument is explicit version
VERSION=$1
HIGHLIGHT_COLOR='\033[0;33m'
if [ -n "$VERSION" ]; then
  ver-bump -v $VERSION -p origin
else
  ver-bump -p origin
fi
git checkout development
last_branch=$(git branch --contains `git rev-list --tags --max-count=1` release*)
printf "\n\n${HIGHLIGHT_COLOR}merging development branch with ${last_branch}\n"
git merge $last_branch
git push --set-upstream origin development
