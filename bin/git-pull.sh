#!/bin/bash
private/share/vukbgit/simplex/bin/clean-cache.sh
git pull
composer update
yarn.sh install
