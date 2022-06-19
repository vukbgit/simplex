#!/bin/bash
#input
read -p "app password/key:" repositoryKey
read -p "git user.email:" userEmail
read -p "git user.name:" userName
cd simplex
#set git origin
git remote remove origin
git remote add origin https://$repositoryKey@github.com/vukbgit/simplex.git
#set git user
git config user.email "$userEmail"
git config user.name "$userName"
#get last tag
tag=$(git describe --tags `git rev-list --tags --max-count=1`)
#switch to last tag
git checkout $tag -b development
