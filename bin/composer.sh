#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#get composer
COMPOSER_PATH=$(which composer)
#execute command
command=$*
$PHP_CLI $COMPOSER_PATH $command
