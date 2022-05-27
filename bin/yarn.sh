#!/bin/bash
#this script is symlinked into the root folder and calls the script located into the folder where npm libraries are installed
command=$*
/bin/bash public/share/yarn.sh $command
