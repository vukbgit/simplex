#!/bin/bash
#get id to process
id=$1
source="private/local/simplex/config/sass"
#read file into an array
scssFiles=( `cat $source `)
#loop file rows
for row in "${scssFiles[@]}"; do
    #break row into an array
    IFS=':' read -ra scssFileDefinition <<< "$row"
    #check if row correspndes to the id to process
    if [ "$id" = "${scssFileDefinition[0]}" ]; then
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
                    echo "creating $tmpPath folder..."
                    #create cuurent folder
                    mkdir $tmpPath
                fi
            #reached the file level
            else
                #compile the file
                echo "compiling $target..."
                sass "${scssFileDefinition[1]}" "$target" --style=compressed;
            fi
        done
    fi
done
