#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#execute command
command=$*
$PHP_CLI "${BASH_SOURCE%/*}/translations.php" $command
 
