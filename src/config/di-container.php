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
use CodeZero\Cookie\VanillaCookie;
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
            return mergeArrayFromFiles(PRIVATE_LOCAL_DIR, 'routes.php');
        },
        //request handler middleware
        'requestHandler' => create(RequestHandler::class)
            ->constructor(get('DIContainer')),
        //dispatcher
        'middlevareQueue' => function(ContainerInterface $DIContainer) {
            return require MIDDLEWARE_QUEUE_PATH;
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
        //template engine, templates path are set into Simplex\ControllerWithTemplateAbstract::renderTemplate
        'twigFilesystemLoader' => create(FilesystemLoader::class)
            ->constructor(),
        'templateEngine' => create(Environment::class)
            ->constructor(get('twigFilesystemLoader')),
        //cookie manager
        'encrypter' => create(DefaultEncrypter::class)
            ->constructor(ENCRYPTION_KEY),
        'cookieManager' => create(VanillaCookie::class)
            ->constructor(get('encrypter')),
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
        'simplexAuthenticationMiddleware' => create(Authentication\Middleware::class)
            ->constructor(get('cookieManager')),
        /**********************************
        * STOP ADDITIONAL FUNCTIONALITIES *
        **********************************/
    ],
    //search definitions into local namespace
    mergeArrayFromFiles(PRIVATE_LOCAL_DIR, 'di-container.php')
);
