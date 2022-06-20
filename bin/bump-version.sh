#!/bin/bash
HIGHLIGHT_COLOR='\033[0;33m'
ver-bump -p origin
git checkout development
last_branch=$(git branch --contains `git rev-list --tags --max-count=1` release*)
printf "\n\n${HIGHLIGHT_COLOR}merging development branch with ${last_branch}\n"
git merge $last_branch
git push --set-upstream origin development
