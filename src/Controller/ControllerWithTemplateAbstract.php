<?php
declare(strict_types=1);

namespace Simplex\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use CodeZero\Cookie\VanillaCookie;
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
abstract class ControllerWithTemplateAbstract extends ControllerAbstract
{
    /**
    * @var Environment
    * Template engine
    */
    protected $template;

    /**
    * @var array
    * parameters to be passed to template engine
    */
    protected $templateParameters = [];

    /**
    * @var string
    * page title, content of the title tag, mandatory for template rendering
    */
    protected $pageTitle;

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    * @param Environment $twigEnvironment
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response, Environment $templateEngine)
    {
        parent::__construct($DIContainer, $response);
        $this->template = $templateEngine;
        //internationalization
        $this->template->addExtension(new \Twig_Extensions_Extension_I18n());
        //markdown support
        $markdownEngine = new MarkdownEngine\MichelfMarkdownEngine();
        $this->template->addExtension(new MarkdownExtension($markdownEngine));
    }

    /**
    * pass a parameter to the template angine
    * @param string $parameterName
    * @param mixed $parameterValue
    */
    protected function setTemplateParameter(string $parameterName, $parameterValue)
    {
        $this->templateParameters[$parameterName] = $parameterValue;
    }

    /**
    * short alias for the - much used - setTemplateParameter method
    * @param string $parameterName
    * @param mixed $parameterValue
    */
    protected function stp(string $parameterName, $parameterValue)
    {
        $this->setTemplateParameter($parameterName, $parameterValue);
    }

    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        parent::doBeforeActionExecution($request);
        //set common template parameters
        $this->setCommonTemplateParameters();
    }

    /**
    * sets common template parameters
    * @param string $pageTitle
    */
    protected function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('environment', ENVIRONMENT);
        $this->setTemplateParameter('application', APPLICATION);
        $this->setTemplateParameter('publicShareDir', PUBLIC_SHARE_DIR);
        $this->setTemplateParameter('language', $this->language);
        $this->setTemplateParameter('routeParameters', $this->routeParameters);
    }
    
    /**
    * sets page title
    * @param string $pageTitle
    */
    protected function setPageTitle(string $pageTitle)
    {
        $this->pageTitle = $pageTitle;
        $this->setTemplateParameter('pageTitle', $pageTitle);
    }

    /**
    * Adds a template filter
    * @param Callable $function
    */
    protected function addTemplateFilter(string $name, Callable $function)
    {
        $filter = new \Twig\TwigFilter($name, $function);
        $this->template->addFilter($filter);
    }

    /**
    * Adds a template function
    * @param Callable $function
    */
    protected function addTemplateFunction(string $name, Callable $function)
    {
        $filter = new \Twig\TwigFunction($name, $function);
        $this->template->addFunction($filter);
    }

    /**
    * Renders template
    * @param string $templatePath: if null, into current namespace will be searched into 'templates' subfolder a template named after $this->action
    */
    protected function renderTemplate(string $templatePath = null)
    {
        //check page title
        if(!$this->pageTitle) {
            throw new \Exception('Page has no title, uset setPageTitle() method to set it');
        }
        //add templates paths
        $loader = $this->template->getLoader();
        $loader->addPath(SHARE_TEMPLATES_DIR, 'share');
        $loader->addPath(LOCAL_TEMPLATES_DIR, 'local');
        $this->template->setLoader($loader);
        //build default path into calling class namespace
        if(!$templatePath) {
            //turn namespace into an array
            $calledClass = get_called_class();
            $classPath = explode('\\', $calledClass);
            //eliminate namespace first 2 elements (Simplex\Local) and last one (current class name)
            //and add templates default folder
            $templatesFolder = implode('/', array_merge(array_slice($classPath, 2, count($classPath) - 3), [TEMPLATES_DEFAULT_FOLDER]));
            $templatePath = sprintf('@local/%s/%s.%s', $templatesFolder, $this->action, TEMPLATES_EXTENSION);
        }
        //render template and get HTML
        $html = $this->template->render($templatePath, $this->templateParameters);
        //send HTML to response
        $response = $this->response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write($html);
    }
}
