#!/bin/bash
#first argument is repository URL, for script automation
repositoryUrl=$1

read -p "git remote branch name [main]:" branch
branch=${branch:-main}
if [ -z "$repositoryUrl" ]
        then
                echo "Have you created repository and made first commit?"
                        select yn in "Yes" "No"; do
                                case $yn in
                                Yes ) 
                                        rm -rf .git
                                        git init -b $branch
                                        git config pull.rebase false
                                        read -p "Repository URL:" repositoryUrl
                                        git remote add origin $repositoryUrl
                                        git fetch --all
                                        git reset --hard origin/$branch
                                        git branch --set-upstream-to=origin/$branch
                                        break;;
                                No ) exit;;
                                esac
                        done
else
        git init
        git remote add origin $repositoryUrl
        git fetch --all
        git reset --hard origin/$branch
        git branch --set-upstream-to=origin/$branch
fi
