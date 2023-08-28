#!/bin/bash
#=============================================
# outputs message formatted according to type
# OPTIONS
# $1 message type: D (=default)| S (=success) | E (=error) | H (=highlight) | U (=undertone)
# $2 message: color codes can be inserted into string for inline color by escaping color variables, i.e \${HC} but third parameter must be set to true
# $3 inlineColors: enable interpretation of backslash escapes on message echo (to evaluate color variables) but in this case message characters like round parenthsys or apostrophe must be backlash escaped
function outputMessage() {
  RED='\033[0;31m'
  GREEN='\033[0;32m'
  ORANGE='\033[0;33m'
  LIGHT_GRAY='\033[0;37m'
  DARK_GRAY='\033[0;90m'
  DEFAULT_COLOR=$LIGHT_GRAY
  DC=$DEFAULT_COLOR
  HIGHLIGHT_COLOR=$ORANGE
  HC=$HIGHLIGHT_COLOR
  ERROR_COLOR=$RED
  EC=$ERROR_COLOR
  SUCCESS_COLOR=$GREEN
  SC=$SUCCESS_COLOR
  UNDERTONE_COLOR=$DARK_GRAY
  UC=$UNDERTONE_COLOR
  
  messageType=$1
  message=$2
  inlineColors=${3:-false}
  color="${messageType}C"
  #type color
  printf ${!color}
  if $inlineColors; then
    eval echo -e "$message"
  else
    echo "$message"
  fi
  #back to default color
  printf $DC
}
#try to grab path to ini config file from environmet variable
PATH_TO_INI_CONFIG=$PATH_TO_INI_CONFIG
#$PATH_TO_INI_CONFIG variable not set
if [ -z $PATH_TO_INI_CONFIG ]; then
  #try to grab it from -i option
  while getopts ":i:" option; do
    case $option in
      i)
        PATH_TO_INI_CONFIG=$OPTARG;;
    esac
  done
  #$PATH_TO_INI_CONFIG variable not set, exit
  if [ -z $PATH_TO_INI_CONFIG ]; then
    outputMessage "E" "path to ini config file must be passed as PATH_TO_INI_CONFIG environment variable or as -i option, exit"
    exit 1
  fi
fi
#valid config file path
if test -f "$PATH_TO_INI_CONFIG"; then
  #include ini config file
  source $PATH_TO_INI_CONFIG
else
#invalid config file path
  outputMessage "E" "path to ini config file $PATH_TO_INI_CONFIG is not valid, exit"
  exit 1
fi
#check mandatory configuration
declare -a mandatory_settings=("ENVIRONMENT" "ABS_PATH_TO_ROOT" "TMP_DIR" "PHP_CLI")
MANDATORY_MISSING=false
for i in "${mandatory_settings[@]}"
  do
    if [ -z ${!i+x} ]; then
      outputMessage "E" "${i} setting must be set into $PATH_TO_INI_CONFIG ini config file";
      MANDATORY_MISSING=true
    fi
  done
if $MANDATORY_MISSING; then
  outputMessage "E" "missing mandatory configuration settings, exit";
  exit 1
fi
