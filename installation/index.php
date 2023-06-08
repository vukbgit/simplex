<?php
declare(strict_types=1);
//set path to ini config file
$pathToIniConfig = false;
//bootstrap
require_once './private/share/packagist/vukbgit/simplex/bin/bootstrap.php';
/*********************
* NAMESPACES ALIASES *
*********************/
use Laminas\Diactoros\ServerRequestFactory;
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
//~r($response->getStatusCode());
$emitter = $DIContainer->get('emitter');
$emitter->emit($response);
 
