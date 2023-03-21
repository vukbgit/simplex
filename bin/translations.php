<?php
declare(strict_types=1);
/**********************
* CHECK USED INTERFACE *
**********************/
if(php_sapi_name() !== 'cli') {
    die(sprintf('script %s can be called ONLY by cli interface%s', $argv[0], PHP_EOL));
}
/*******************
* SCRIPT ARGUMENTS *
*******************/
//operation
$possibleOperations = ['create', 'update'];
$possibleContexts = ['share', 'local'];
if(!isset($argv[1]) || !isset($argv[2]) || !in_array($argv[1], $possibleOperations) || !in_array($argv[2], $possibleContexts)) {
    die(sprintf('script %s must be called passing "operation" as first argument with possible values %s and "context" as second argument with possible values %s%s', $argv[0], implode(' or ', $possibleOperations), implode(' or ', $possibleContexts), PHP_EOL));
} else {
    $operation = $argv[1];
}
//context
$context = $argv[2] ?? null;
/**********************
* IMPORTED NAMESPACES *
**********************/
use DI\ContainerBuilder;
use function Simplex\requireFromFiles;
/********
* PATHS *
********/
$absPathToRoot = str_replace('/private/share/packagist/vukbgit/simplex/bin', '', __DIR__);
/***********
* COMPOSER *
***********/
require_once sprintf('%s/private/share/packagist/autoload.php', $absPathToRoot);
/**************
* ENVIRONMENT *
**************/
//mock developement environment usually defined into .htaccess
putenv('REDIRECT_ENVIRONMENT=development');
//include from local namespace all of constants definition files
requireFromFiles(sprintf('%s/private/local/simplex', $absPathToRoot), 'constants.php');
/************
* CONTAINER
* definitions into private/local/simplex/config/di-container.php
************/
$DIContainerBuilder = new ContainerBuilder();
$DIContainerBuilder->useAutowiring(false);
$DIContainerBuilder->addDefinitions(require sprintf('%s/di-container.php', SHARE_CONFIG_DIR));
$DIContainer = $DIContainerBuilder->build();

/********************************
* build local templates helpers *
********************************/
foreach (\Nette\Utils\Finder::findFiles('templates-helpers.php')->from(sprintf('%s/private/local/simplex', $absPathToRoot)) as $file) {
    $filePath = $file->__toString();
    require $filePath;
}
/*************************
* TRANSLATIONS EXTRACTOR *
*************************/
$translationsExtractor = $DIContainer->get('translationsExtractor');
$translationsExtractor->extractTranslations($operation, $context);
