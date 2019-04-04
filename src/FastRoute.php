<?php
declare(strict_types = 1);

namespace Simplex;

use FastRoute\Dispatcher;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Traits\HasResponseFactory;

/**
 * Routing Middleware that uses nikic/fastroute
 * Based on Middlewares\FastRoute with the addition of additionl route parameter outside of route patterns
 *
 * @author vuk <info@vuk.bg.it>
 */
class FastRoute implements MiddlewareInterface
{
    use HasResponseFactory;

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
     * @param Dispatcher $router
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(Dispatcher $router, ResponseFactoryInterface $responseFactory = null)
    {
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
        $route = $this->router->dispatch($request->getMethod(), rawurldecode($request->getUri()->getPath()));

        if ($route[0] === Dispatcher::NOT_FOUND) {
            return $this->createResponse(404);
        }

        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->createResponse(405)->withHeader('Allow', implode(', ', $route[1]));
        }
        $parameters = [];
        if(isset($route[1][1])) {
            foreach ($route[1][1] as $name => $value) {
                $parameters[$name] = $value;
            }
        }
        foreach ($route[2] as $name => $value) {
            $parameters[$name] = $value;
        }
        $request = $request->withAttribute('parameters', (object) $parameters);

        $request = $this->setHandler($request, $route[1][0]);

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
