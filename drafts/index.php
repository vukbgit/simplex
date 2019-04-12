<?php
declare(strict_types=1);
/***********
* COMPOSER *
***********/
require_once 'private/share/autoload.php';
/**********************
* IMPORTED NAMESPACES *
**********************/
use DI\ContainerBuilder;
use Zend\Diactoros\ServerRequestFactory;
/**************
* ENVIRONMENT *
**************/
require 'private/local/simplex/config/constants.php';
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
    default:
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
$DIContainerBuilder->useAnnotations(false);
$DIContainerBuilder->addDefinitions(require sprintf('%s/config/di-container.php', SHARE_DIR));
$DIContainer = $DIContainerBuilder->build();
/*****************************************
* REQUEST HANDLER CONTAINER / DISPATCHER
* middleware queue is injected by DIContainer from
* private/local/simplex/config/middleware.php
*****************************************/
$dispatcher = $DIContainer->get('dispatcher');
$response = $dispatcher->dispatch(ServerRequestFactory::fromGlobals());
/**************
* HTTP ERRORS *
**************/
/*$HTTPStatusCode = $response->getStatusCode();
if($HTTPStatusCode !== 200) {
    $pathToErrorFile = sprintf('%s/%s.html', ERROR_DIR, $HTTPStatusCode);
    $response->getBody()
        ->write(file_get_contents($pathToErrorFile));
}*/
/*****************************
* SEND RESPONSE TO WEBSERVER *
*****************************/
//~r($response->getStatusCode());
$emitter = $DIContainer->get('emitter');
$emitter->emit($response);
/*foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        @header(sprintf('%s: %s', $name, $value), false);
    }
}
// output body
echo $response->getBody();
*/
