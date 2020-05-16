#!/bin/bash
#first argument is repository URL, for script automation
repositoryUrl=$1

if [ -z "$repositoryUrl" ]
        then
                echo "Have you created repository and made first commit?"
                        select yn in "Yes" "No"; do
                                case $yn in
                                Yes ) 
                                        rm -rf .git
                                        git init
                                        read -p "Repository URL:" repositoryUrl
                                        git remote add origin $repositoryUrl
                                        git fetch --all
                                        git reset --hard origin/master
                                        git branch --set-upstream-to=origin/master
                                        break;;
                                No ) exit;;
                                esac
                        done
else
        git init
        git remote add origin $repositoryUrl
        git fetch --all
        git reset --hard origin/master
        git branch --set-upstream-to=origin/master
fi

