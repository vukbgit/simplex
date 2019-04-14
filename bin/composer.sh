#!/bin/bash
pathToPHPBin=/opt/php-7.2.6/bin/php
command=$*
$pathToPHPBin /usr/local/bin/composer $command
