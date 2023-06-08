#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#this script is symlinked into the root folder and calls the script located into the folder where npm libraries are installed
command=$*
outputMessage "H" "Executing npm command: $command"
/bin/bash public/share/npm.sh $command
