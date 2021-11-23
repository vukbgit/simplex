#!/bin/bash
pathToPHPBin=/opt/php-7.3.5/bin/php
command=$*
$pathToPHPBin private/share/packagist/vukbgit/simplex/bin/translations.php $command
