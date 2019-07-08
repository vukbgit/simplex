#!/bin/bash
private/share/vukbgit/simplex/bin/clean-cache.sh
git pull
./composer.sh update
./yarn.sh install
