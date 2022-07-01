#!/bin/bash
#this script will be called from private/share/vukbgit/simplex/bin/yarn.sh
command=$*
#so we need to cd into this folder
cd public/share
npm $command
