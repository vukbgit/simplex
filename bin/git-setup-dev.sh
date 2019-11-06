#!/bin/bash
echo "Have you checked and customized .gitignore file?"
select yn in "Yes" "No"; do
    case $yn in
    Yes ) 
        rm -rf .git
        git init
        read -p "Repository URL:" repositoryUrl
        git remote add origin $repositoryUrl
        git fetch
        read -p "git user.email:" userEmail
        git config user.email "$userEmail"
        read -p "git user.name:" userName
        git config user.name "$userName"
        git config push.default simple
        git add private/
        git add public/
        git add .gitignore
        git add .htaccess
        git add index.php
        git add composer.json
        git commit -m "first complete commit"
        git push --set-upstream origin master
        break;;
    No ) exit;;
    esac
done
