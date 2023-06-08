#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
git pull
if [ -f ./composer.sh ]; then
  ./composer.sh update
else
  composer update
fi
#check public package manager
FOLDER=public/share
if [[ -f "${FOLDER}/package-lock.json" ]]; then
  ./npm.sh update
else
  outputMessage "E" "no public package managerinitiated, please use ./npm.sh or ./yarn.sh to install packages"
fi
#clean cache
${BASH_SOURCE%/*}/clean-cache.sh
