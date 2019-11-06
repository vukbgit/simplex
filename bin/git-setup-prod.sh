#!/bin/bash
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
        git checkout -t origin/master
        break;;
    No ) exit;;
    esac
done
