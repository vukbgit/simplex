#!/bin/bash
private/share/packagist/vukbgit/simplex/bin/clean-cache.sh
git pull
if [ -f ./composer.sh ]; then
    ./composer.sh update
else
    composer update
fi
#check public package manager
FOLDER=public/share
if [[ -f "${FOLDER}/package-lock.json" ]]; then
    echo "updating npm"
    ./npm.sh update
elif [[ -f "${FOLDER}/yarn.lock" ]]; then
    echo "updating yarn"
    ./yarn.sh install
    ./yarn.sh upgrade
else
    echo "no public package managerinitiated, please use ./npm.sh or ./yarn.sh to install packages"
fi
