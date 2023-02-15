<?php
declare(strict_types=1);
/**********************
* IMPORTED NAMESPACES *
**********************/
use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequestFactory;
use function Simplex\requireFromFiles;
/***********
* COMPOSER *
***********/
require_once 'private/share/packagist/autoload.php';
/**************
* ENVIRONMENT *
**************/
//include from local namespace all of constants definition files
requireFromFiles('private/local/simplex', 'constants.php');
/*****************
* ERROR HANDLING *
*****************/
$whoops = new \Whoops\Run(null);
//error reporting
ini_set("display_errors", "1");
error_reporting(E_ALL);
//exception handler
switch(ENVIRONMENT) {
    case 'development':
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    break;
    case 'production':
        $whoops->pushHandler(new \Simplex\ErrorHandler);
    break;
}
$whoops->register();
/************
* CONTAINER
* definitions into private/local/simplex/config/di-container.php
************/
$DIContainerBuilder = new ContainerBuilder();
//cache
switch(ENVIRONMENT) {
    case 'production':
        $DIContainerBuilder->enableCompilation(TMP_DIR);
        $DIContainerBuilder->writeProxiesToFile(true, TMP_DIR . '/proxies');
    break;
}
$DIContainerBuilder->useAutowiring(false);
$DIContainerBuilder->addDefinitions(require sprintf('%s/di-container.php', SHARE_CONFIG_DIR));
$DIContainer = $DIContainerBuilder->build();
/*****************************************
* REQUEST HANDLER CONTAINER / DISPATCHER
* middleware queue is injected by DIContainer from
* MIDDLEWARE_QUEUE_PATH
*****************************************/
$dispatcher = $DIContainer->get('dispatcher');
$response = $dispatcher->dispatch(ServerRequestFactory::fromGlobals());
/**************
* HTTP ERRORS *
**************/
$HTTPStatusCode = $response->getStatusCode();
//error happened without redirection
if($HTTPStatusCode !== 200 && !$response->hasHeader('Location')) {
    $pathToErrorFile = sprintf('%s/%s.html', ERROR_DIR, $HTTPStatusCode);
    $response->getBody()
        ->write(file_get_contents($pathToErrorFile));
}
/*****************************
* SEND RESPONSE TO WEBSERVER *
*****************************/
$emitter = $DIContainer->get('emitter');
//in case any output disturbs emitter, catch the exception and display the output
try {
    $emitter->emit($response);
} catch(\Exception $e) {
    xx($e);
}
