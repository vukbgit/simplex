#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
outputMessage "H" "cleaning cache..."
rm -rf $TMP_DIR/[!sess_]*
outputMessage "S" "cache cleaned"
