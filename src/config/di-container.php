<?php
declare(strict_types=1);
//PHP-DI functions
use function DI\create;
use function DI\get;
//MINIMAL FUNCTIONALITIES
use Psr\Container\ContainerInterface;
use Simplex\FastRouteMiddleware;
use Zend\Diactoros\Response;
use Middlewares\RequestHandler;
use Middleland\Dispatcher;
use Narrowspark\HttpEmitter\SapiEmitter;
//ADDITIONAL FUNCTIONALITIES
//template engine
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
//query builder
use \Pixie\Connection;
use \Simplex\PixieExtended;
//authentication
use \Simplex\Authentication;
//to get LOCAL CLASSES
use function Simplex\mergeArrayFromFiles;
//definitions array
return array_merge(
    [
        /********************************
        * START MINIMAL FUNCTIONALITIES *
        ********************************/
        //DI container
        'DIContainer' => function(ContainerInterface $c) {
            return $c;
        },
        //router
        'simplexFastRouteMiddleware' => create(FastRouteMiddleware::class)
            ->constructor(ENVIRONMENT, get('routes'), TMP_DIR),
        'routes' => function() {
            //search routes definitions into local namespace
            return mergeArrayFromFiles(LOCAL_DIR, 'routes.php');
        },
        //request handler middleware
        'requestHandler' => create(RequestHandler::class)
            ->constructor(get('DIContainer')),
        //dispatcher
        'middlevareQueue' => function(ContainerInterface $DIContainer) {
            return require sprintf('%s/middleware.php', SHARE_CONFIG_DIR);
        },
        'dispatcher' => create(Dispatcher::class)
            ->constructor(get('middlevareQueue'), get('DIContainer')),
        //http response
        'response' => create(response::class),
        //emitter
        'emitter' => create(SapiEmitter::class),
        /*******************************
        * STOP MINIMAL FUNCTIONALITIES *
        *******************************/
        /***********************************
        * START ADDITIONAL FUNCTIONALITIES *
        ***********************************/
        //template engine
        'templatesFolder' => [LOCAL_TEMPLATES_DIR, SHARE_TEMPLATES_DIR],
        'twigFilesystemLoader' => create(FilesystemLoader::class)
            ->constructor(get('templatesFolder')),
        'templateEngine' => create(Environment::class)
            ->constructor(get('twigFilesystemLoader')),
        //query builder
        'dbConfig' => function(){
            $config = require sprintf('%s/db.php', LOCAL_CONFIG_DIR);
            if(!isset($config[ENVIRONMENT])) {
                throw new \Exception(sprintf('There is no databsae configuration for current environment \'%s\'', ENVIRONMENT));
            }
            return $config[ENVIRONMENT];
        },
        'pixieConnection' => create(Connection::class)
            ->constructor('mysql', get('dbConfig')),
        'queryBuilder' => create(PixieExtended::class)
            ->constructor(get('pixieConnection')),
        //authentication
        'simplexAuraAuth' => create(Authentication\AuraAuth::class)
            ->constructor(get('DIContainer')),
        'login' => create(Authentication\Login::class)
            ->constructor(
                get('DIContainer'),
                get('response'),
                get('templateEngine')
        ),
        /**********************************
        * STOP ADDITIONAL FUNCTIONALITIES *
        **********************************/
    ],
    //search definitions into local namespace
    mergeArrayFromFiles(LOCAL_DIR, 'di-container.php')
);
