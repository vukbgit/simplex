#!/bin/bash
pathToPHPBin=/opt/php-7.3.5/bin/php
command=$*
$pathToPHPBin /usr/local/bin/composer $command
