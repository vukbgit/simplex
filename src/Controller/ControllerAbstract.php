<?php
declare(strict_types=1);

namespace Simplex\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Twig\Environment;

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
    * @var Environment
    * Twig environment
    */
    protected $twig;

    /**
    * @var object
    * values extracted from route parsing
    */
    protected $routeParameters;

    /**
    * @var string
    * action passed by route
    */
    protected $action;

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    * @param Environment $twigEnvironment
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response, Environment $twigEnvironment)
    {
        $this->DIContainer = $DIContainer;
        $this->response = $response;
        $this->twig = $twigEnvironment;
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
        //store request and its parameters
        $this->request = $request;
        $this->routeParameters = $request->getAttributes()['parameters'];
        //store route action
        $this->action = $this->routeParameters->action ?? null;
        //action is set
        if($this->action) {
            //build method name
            $methodName = $this->buildMethodName($this->action);
            //method exists
            if(method_exists($this, $methodName)) {
                //call method
                call_user_func([$this, $methodName]);
            //method does NOT exist
            } else {
                throw new \Exception(sprintf('current route is associated to action \'%s\' but method \'%s\' of class %s does not exist', $this->action, $methodName, static::class));
            }
        //action is NOT set
        } else {
            throw new \Exception('current route *MUST* pass an \'action\' parameter or __invoke() method should be overridden into concrete class');
        }
        //return response
        return $this->response;
    }

    /**
    * Build method name from route action
    * @param string $action
    *
    * @return string
    */
    private function buildMethodName(string $action) : string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));
    }

    /**
    * Renders template
    * @param string $templatePath: if null, into current namespace will be searched into 'templates' subfolder a template named after $this->action
    */
    protected function renderTemplate(string $templatePath = null)
    {
        //build default path into calling class namespace
        if(!$templatePath) {
            //turn namespace into an array
            $calledClass = get_called_class();
            $classPath = explode('\\', $calledClass);
            //eliminate namespace first 2 elements (Simplex\Local) and last one (current class name)
            //and add templates default directory
            $templatesFolder = implode('/', array_merge(array_slice($classPath, 2, count($classPath) - 3), ['templates']));
            $templatePath = sprintf('%s/%s.%s',$templatesFolder , $this->action, TEMPLATES_EXTENSION);
        }
        //render template and get HTML
        $html = $this->twig->render($templatePath);
        //send HTML to response
        $response = $this->response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write($html);
    }
}
