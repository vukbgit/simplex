<?php
declare(strict_types = 1);

namespace Simplex\Authentication;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Aura\Auth\Session\Segment;
use Aura\Auth\AuthFactory;

/**
 * Routing Middleware that uses nikic/fastroute
 * Based on Middlewares\FastRoute with the addition of routes definition processing and additionl route parameters outside of route patterns
 *
 * @author vuk <info@vuk.bg.it>
 */
class AuraAuth implements MiddlewareInterface
{
    /**
    * @var ContainerInterface
    * DI container, to create Aura Session based on route definition
    */
    protected $DIContainer;

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    */
    public function __construct(ContainerInterface $DIContainer)
    {
        $this->DIContainer = $DIContainer;
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
        //r('authentication');
        //check route parameter
        $requestParameters = $request->getAttributes()['parameters'];
        //get area
        $area = $requestParameters->authentication->area ?? null;
        //create session segment
        if($area) {
            $sessionSegment = new Segment($area);
        } else {
            throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\'][\'area\'] parameter');
        }
        //create auth instance
        $authFactory = new AuthFactory($_COOKIE, null, $sessionSegment);
        $auth = $authFactory->newInstance();
        //check current authentication status
        $logStatus = $auth->getStatus();
        //call handler to get response
        $response = $handler->handle($request);
        //failure
        if($logStatus === 'ANON') {
            $failureURL = $requestParameters->authentication->failureURL ?? null;
            //redirect on failure
            if($failureURL) {
                $response = $response->withHeader('Location', $failureURL);
                //set status to "Found" "The requested resource resides temporarily under a different URI" (https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html)
                $response = $response->withStatus('302');
            } else {
                throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\'][\'failureURL\'] parameter');
            }
        }
        return $response;
    }
}
