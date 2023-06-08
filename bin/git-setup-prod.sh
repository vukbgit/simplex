#!/bin/bash
#bootstrap
source "${BASH_SOURCE%/*}/bootstrap.sh"
#first argument is repository URL (complete with app password/key), for script automation
repositoryUrl=$1
#prompt
read -p "git remote branch name [main]:" branch
branch=${branch:-main}
if [ -z "$repositoryUrl" ]
  then
    outputMessage "H" "Have you created repository and made first commit?"
    select yn in "Yes" "No"; do
      case $yn in
      Yes ) 
        rm -rf .git
        git init -b $branch
        git config pull.rebase false
        read -p "Repository URL (complete with app password/key):" repositoryUrl
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
