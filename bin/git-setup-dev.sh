#!/bin/bash

echo "Have you checked and customized .gitignore file?"
select yn in "Yes" "No"; do
    case $yn in
    Yes ) 
        read -p "Repository URL (complete with app password/key):" repositoryUrl
        read -p "git user.email:" userEmail
        read -p "git user.name:" userName
        read -p "git remote branch name [main]:" branch
        branch=${branch:-main}
        rm -rf .git
        #git init -b $branch
        #git config user.email "$userEmail"
        #git config user.name "$userName"
        #git config push.default simple
        #git config pull.rebase false
        #git remote add origin $repositoryUrl
        #git fetch
        #git checkout --track origin/$branch
        #git pull 
        #git add private/
        #git add public/
        #git add .gitignore
        #git add .htaccess
        #git add index.php
        #git add composer.json
        #git commit -m "first complete commit"
        #git push
        git init -b $branch
        git config user.email "$userEmail"
        git config user.name "$userName"
        git config push.default simple
        git config pull.rebase false
        git add private/
        git add public/
        git add .gitignore
        git add .htaccess
        git add index.php
        git add composer.json
        git commit -m "first complete commit"
        git remote add origin $repositoryUrl
        git push -u origin --all
        break;;
    No ) exit;;
    esac
done
