#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#turn argoments into an array of ids to compile
ids=( "$@" )
argsNum=${#ids[@]}
source="private/local/simplex/config/sass.config"
#read file into an array
#scssFiles=( `cat $source`)
mapfile scssFiles < $source
#loop file rows
for row in "${scssFiles[@]}"; do
    #skip comments row starting with #
    if [[ ${row:0:1} == "#" ]]; then
        continue
    fi
    #break row into an array
    IFS=':' read -ra scssFileDefinition <<< "$row"
    #check if row correspondes to the id to process
    #if [ "$id" = "${scssFileDefinition[0]}" ]; then
    if [[ " ${ids[*]} " == *" ${scssFileDefinition[0]} "* ]] || [ "$argsNum" == 0 ]; then
        #get target path
        target=${scssFileDefinition[2]}
        #break target path into an array
        IFS='/' read -ra targetPath <<< "$target"
        pathLength=${#targetPath[@]}
        targetFileIndex=$((pathLength-1))
        #init temporary path and iteration folder
        tmpPath=""
        folder=""
        #loop over target folder path to build it if necessary
        for i in "${!targetPath[@]}"; do
            #check that the item is not the file
            if [ "$i" -lt "$targetFileIndex" ]; then
                #store current folder
                folder="${targetPath[$i]}"
                #increment temporary path
                tmpPath+="$folder/"
                #check if current folder does not exists
                if [ ! -d  "$tmpPath" ]; then
                    outputMessage "H" "creating $tmpPath folder..."
                    #create cuurent folder
                    mkdir $tmpPath
                fi
            #reached the file level
            else
                #compile the file
                outputMessage "H" "compiling $target..."
                sass --load-path=public/share/node_modules --load-path=private "${scssFileDefinition[1]}" "$target" --style=compressed;
            fi
        done
    fi
done
