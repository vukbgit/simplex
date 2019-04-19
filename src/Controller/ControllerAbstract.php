<?php
declare(strict_types=1);

namespace Simplex\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use function Simplex\slugToPSR1Name;

/*
* In this context controller means a class that:
* - is associated as a handler to a route
* - gets invoked by the request handler middleware, so must be callable, so must have an __invoke magic method
* - receives the request object ($this->request) and the parameters defined for the route ($this->routeParameters)
* - if a route parameter named 'action' exists:
*       - the predefined __invoke method tries to call a public or protected method of the concrete class named after that parameter value
*       - the method name SHOULD be camelcased, so the parameter value SHOULD have hypens (-) to separate words
* - otherwise the __invoke method must be override by concrete derived class
*/
abstract class ControllerAbstract
{
    /**
    * @var ServerRequestInterface
    */
    protected $request;

    /**
    * @var ResponseInterface
    */
    protected $response;

    /**
    * @var ContainerInterface
    * DI container, to create/get instances of classes needed at runtime (such as models)
    */
    protected $DIContainer;

    /**
    * @var object
    * values extracted from route parsing
    */
    protected $routeParameters;

    /**
    * @var object
    * object with current language specifications
    */
    protected $language;

    /**
    * @var string
    * action passed by route
    */
    protected $action;

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response)
    {
        $this->DIContainer = $DIContainer;
        $this->response = $response;
    }

    /**
     * Get invoked by request handler
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //before action execution
        $this->doBeforeActionExecution($request);
        //handle action
        $this->handleActionExecution();
        //return response
        return $this->response;
    }

    /**
    * Stores request and related informations
    * @param ServerRequestInterface $request
    */
    protected function storeRequest(ServerRequestInterface $request)
    {
        //store request and its parameters
        $this->request = $request;
        $this->routeParameters = $request->getAttributes()['parameters'];
        //store route action
        $this->action = $this->routeParameters->action ?? null;
    }

    /**
    * Gets language
    */
    protected function setLanguage()
    {
        $languageCode = $this->routeParameters->lang ?? null;
        $languagesConfigFilePath = sprintf('%s/languages.json', LOCAL_CONFIG_DIR);
        $languages = json_decode(file_get_contents($languagesConfigFilePath));
        $this->language = $languages->$languageCode ?? current($languages);
    }

    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        //store request
        $this->storeRequest($request);
        //check language
        $this->setLanguage();
    }

    /**
    * Handles action execution
    */
    protected function handleActionExecution()
    {
        //action is set
        if($this->action) {
            //build method name
            $methodName = slugToPSR1Name($this->action, 'method');
            //method exists
            if(method_exists($this, $methodName)) {
                //call method
                call_user_func([$this, $methodName]);
            //method does NOT exist
            } else {
                throw new \Exception(sprintf('current route is associated to action "%s" but method "%s" of class "%s" does not exist', $this->action, $methodName, static::class));
            }
        //action is NOT set
        } else {
            throw new \Exception('current route MUST pass an "action" parameter or __invoke() method should be overridden into concrete class');
        }
    }
}
