<?php

declare(strict_types=1);

namespace Simplex;

use FastRoute\RouteCollector;

/*********
* ROUTER *
*********/
if (! function_exists('Simplex\router')) {
    /**
     * Sets up router importing routes definitions
     */
    function router()
    {
        //set routes caching
        switch(ENVIRONMENT) {
            case 'production':
                $fastRouteDispatcherClass = 'FastRoute\cachedDispatcher';
                $fastRouteCacheOptions = [
                    'cacheFile' => '../tmp/fastroute.cache', /* required */
                    'cacheDisabled' => false,     /* optional, enabled by default */
                ];
            break;
            default:
                $fastRouteDispatcherClass = 'FastRoute\simpleDispatcher';
                $fastRouteCacheOptions = [];
            break;
        }
        $router = $fastRouteDispatcherClass(
            function (RouteCollector $r) {
                $routes = require 'private/local/simplex/config/routes.php';
                //$r->get('/x', HelloWorld::class);
                foreach ($routes as $route) {
                    $r->addRoute($route['method'], $route['route'], $route['handler']);
                }
                /*$r->addRoute('GET', '/x/{par}', function ($request) {
                    $par = $request->getAttribute('par');
                    //r($par);
                });*/
            },
            $fastRouteCacheOptions
        );

        return $router;
    }
}
