<?php
declare(strict_types = 1);

namespace Simplex;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Traits\HasResponseFactory;

/**
 * Routing Middleware that uses nikic/fastroute
 * Based on Middlewares\FastRoute with the addition of routes definition processing and additionl route parameters outside of route patterns
 *
 * @author vuk <info@vuk.bg.it>
 */
class FastRouteMiddleware implements MiddlewareInterface
{
    /**
     * @var Dispatcher FastRoute dispatcher
     */
    private $router;

    /**
     * @var string Attribute name for handler reference
     */
    private $attribute = 'request-handler';

    /**
     * Set the Dispatcher instance and optionally the response factory to return the error responses.
     * @param string $environment: 'development': no cache used | any other value routes are cached
     * @param array $routes: array of routes definition
     * @param string $tmpFolderPath: to store cache file when cache is used
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(string $environment, array $routes, string $tmpFolderPath = null, ResponseFactoryInterface $responseFactory = null)
    {
        //until PHP 7.3 var_export uses stdClass::__setState() which causes problems
        if($environment == 'production' && version_compare(PHP_VERSION, '7.3.0') >= 0) {
            $fastRouteDispatcherClass = 'FastRoute\cachedDispatcher';
            $fastRouteCacheOptions = [
                'cacheFile' => $tmpFolderPath . '/fastroute.cache', /* required */
                'cacheDisabled' => false,     /* optional, enabled by default */
            ];
        }else {
            $fastRouteDispatcherClass = 'FastRoute\simpleDispatcher';
            $fastRouteCacheOptions = [];
        }
        //router instance
        $router = $fastRouteDispatcherClass(
            function (RouteCollector $r) use ($routes) {
                //add routes
                foreach ($routes as $route) {
                    $r->addRoute($route['method'], $route['route'], $route['handler']);
                }
            },
            $fastRouteCacheOptions
        );
        $this->router = $router;
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Process a server request and return a response.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //get current matching route (/if any)
        $route = $this->router->dispatch($request->getMethod(), rawurldecode($request->getUri()->getPath()));
        //handle errors
        if ($route[0] === Dispatcher::NOT_FOUND) {
            return $this->createResponse(404);
        }
        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->createResponse(405)->withHeader('Allow', implode(', ', $route[1]));
        }
        //store parameters
        $parameters = [];
        //static parameters hard coded into route definition
        if(isset($route[1][1])) {
            foreach ($route[1][1] as $name => $value) {
                $parameters[$name] = $value;
            }
        }
        //route pattern parameters (overrides static ones with same name)
        foreach ($route[2] as $name => $value) {
            $parameters[$name] = $value;
        }
        $request = $request->withAttribute('parameters', (object) $parameters);
        //set handler
        $request = $this->setHandler($request, $route[1][0]);
        //call handler
        return $handler->handle($request);
    }

    /**
     * Set the handler reference on the request.
     *
     * @param ServerRequestInterface $request
     * @param mixed $handler
     *
     * @return ServerRequestInterface
     */
    protected function setHandler(ServerRequestInterface $request, $handler): ServerRequestInterface
    {
        return $request->withAttribute($this->attribute, $handler);
    }
}
