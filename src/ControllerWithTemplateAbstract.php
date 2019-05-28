<?php
declare(strict_types=1);

namespace Simplex;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use Simplex\ControllerAbstract;
use Simplex\VanillaCookieExtended;
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
    * @var VanillaCookieExtended
    * cookies manager
    */
    protected $cookie;

    /**
    * @var array
    * parameters to be passed to template engine
    */
    protected $templateParameters = [];

    /**
    * @var array
    * navigations
    */
    protected $navigations = [];
    
    /**
    * @var array
    * currently selected voice key into navigations, indexed by navigation since each route can be set into different anvigations with different keys
    */
    protected $currentNavigationVoice = [];

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    * @param Environment $twigEnvironment
    * @param VanillaCookieExtended $cookie
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response, Environment $templateEngine, VanillaCookieExtended $cookie)
    {
        parent::__construct($DIContainer, $response);
        $this->template = $templateEngine;
        $this->cookie = $cookie;
        //internationalization
        $this->template->addExtension(new \Twig_Extensions_Extension_I18n());
        //markdown support
        $markdownEngine = new MarkdownEngine\MichelfMarkdownEngine();
        $this->template->addExtension(new MarkdownExtension($markdownEngine));
    }

    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        parent::doBeforeActionExecution($request);
        //build common template helpers
        $this->buildCommonTemplateHelpers();
        //set common template parameters
        $this->setCommonTemplateParameters();
    }

    /***********
    * TEMPLATE *
    ***********/

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
    * Build common template helpers
    */
    protected function buildCommonTemplateHelpers()
    {
        //dumps var in development environment
        $this->addTemplateFunction(
            'dump',
            function($var){
                x($var);
            },
            ['is_safe' => ['html']]
        );
        //returns path to yarn packages asset
        $this->addTemplateFilter('pathToNpmAsset', function(string $path){
            return sprintf('/%s/node_modules/%s', PUBLIC_SHARE_DIR, $path);
        });
        //checks whether a given path is the requested URI  path
        //returns path to yarn packages asset
        $this->addTemplateFilter('isPathCurrentRoute', function($path){
            return $this->isPathCurrentRoute($path);
        });
        //parses a route pattern
        $this->addTemplateFunction(
            'parseRoutePattern',
            function(string $routePattern, array $containers = []){
                //get route pattern placeholders
                preg_match_all('/\{([a-z0-9_]+)\}/', $routePattern, $placeholders);
                $placeholders = $placeholders[1];
                //loop placeholders to find replacements
                $replacements = [];
                foreach ($placeholders as $placeholderIndex => $placeholder) {
                    $placeholders[$placeholderIndex] = sprintf('/{(%s)}/', $placeholder);
                    //loop conteiners
                    foreach ($containers as $container) {
                        $container = (object) $container;
                        //placeholder value found
                        if(isset($container->$placeholder)) {
                            $replacements[$placeholderIndex] = $container->$placeholder;
                            break;
                        }
                        //default placeholder value is null
                        $replacements[$placeholderIndex] = null;
                    }
                }
                $route = preg_replace($placeholders, $replacements, $routePattern);
                return $route;
            },
            ['is_variadic' => true]
        );
        //parses the config object for a form field that uses templates/form/macros 
        $this->addTemplateFunction('parseFieldConfig', function(array $config){
            //turn into objects
            $config = (object) $config;
            $config->validation = isset($config->validation) ? (object) $config->validation : null;
            //check mandatory attributes
            $mandatoryAttributes = ['name'];
            $mandatoryMissing = [];
            foreach($mandatoryAttributes as $attribute) {
                if(!isset($config->$attribute)) {
                    $mandatoryMissing[] = $attribute;
                }
            }
            if(!empty($mandatoryMissing)) {
                $config->validation->isInvalid = true;
                $config->validation->invalidMessage = sprintf('DEV ALERT: this field misses mandatory attribute(s) %s', implode(', ', $mandatoryMissing));
            }
            //return config
            return $config;
        });
        //checks a user permission
        $this->addTemplateFunction('checkPermission', function(string $permission){
            return $this->checkPermission($permission);
        });
    }
    
    /**
    * Sets common template parameters
    */
    protected function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('environment', ENVIRONMENT);
        $this->setTemplateParameter('brand', BRAND);
        $this->setTemplateParameter('area', $this->area);
        $this->setTemplateParameter('language', $this->language);
        $this->setTemplateParameter('routeParameters', $this->routeParameters);
        $this->setTemplateParameter('pathToAreaTemplate', sprintf('@local/%s/%s/%s.twig', slugToPSR1Name($this->area, 'class'), TEMPLATES_DEFAULT_FOLDER, $this->area));
        $this->setTemplateParameter('areaCookie', $this->getAreaCookie());
        $this->setTemplateParameter('cookieDuration', COOKIE_DURATION);
    }
    
    /**
    * Adds a template filter
    * @param string $name
    * @param Callable $function
    * @param array $options twig filter options
    */
    protected function addTemplateFilter(string $name, Callable $function, array $options = [])
    {
        $filter = new \Twig\TwigFilter($name, $function, $options);
        $this->template->addFilter($filter);
    }

    /**
    * Adds a template function
    * @param string $name
    * @param Callable $function
    * @param array $options twig filter options
    */
    protected function addTemplateFunction(string $name, Callable $function, array $options = [])
    {
        $filter = new \Twig\TwigFunction($name, $function, $options);
        $this->template->addFunction($filter);
    }

    /**
    * Renders template
    * @param string $templatePath: if null, into current namespace will be searched into 'templates' subfolder a template named after $this->action
    */
    protected function renderTemplate(string $templatePath = null)
    {
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
        $this->setTemplateParameter('test', $this->cookie->get('test'));
        $html = $this->template->render($templatePath, $this->templateParameters);
        //send HTML to response
        $response = $this->response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write($html);
    }
    
    /*************
    * NAVIGATION *
    *************/
    
    /**
     * Loads area navigation from a file
     * @param string $path: pat to file which return an array of navigations
     */
    protected function loadNavigation(string $path)
    {
        $this->navigations = array_merge($this->navigations, require $path);
        foreach ($this->navigations as $navigationName => &$navigation) {
            $this->loadNavigationLevel($navigationName, $navigation);
        }
        $this->setTemplateParameter('navigations', $this->navigations);
    }
    
    /**
     * Loads a navigation level
     * @param string $navigationName
     * @param array $navigationLevel
     * @param object $parentVoiceProperties: object with properties of curent level's parent
     */
    protected function loadNavigationLevel(string $navigationName, array &$loadedNavigationLevel, object &$parentVoiceProperties = null)
    {
        foreach ($loadedNavigationLevel as $voiceKey => $voiceProperties) {
            //check voice permission
            if(isset($voiceProperties->permissions) && !$this->checkAtLeastOnePermission($voiceProperties->permissions)) {
                unset($loadedNavigationLevel[$voiceKey]);
                continue;
            }
            if($parentVoiceProperties) {
                $voiceProperties->parent =& $parentVoiceProperties;
            }
            //check if its current route
            if(isset($voiceProperties->route) && $this->isPathCurrentRoute($voiceProperties->route)) {
                $voiceProperties->isActive = true;
                $this->currentNavigationVoice[$navigationName] = $voiceKey;
                if($parentVoiceProperties) {
                    $this->setNavigationVoiceParentsActive($parentVoiceProperties);
                }
            }
            //check sub level
            if(isset($voiceProperties->navigation)) {
                $this->loadNavigationLevel($navigationName, $voiceProperties->navigation, $voiceProperties);
            }
        }
    }
    
    /**
     * Sets recursevely voice parent as active
     * @parm object $parentVoiceProperties
     */
    protected function setNavigationVoiceParentsActive(object $parentVoiceProperties)
    {
        $parentVoiceProperties->isActive = true;
        if(isset($parentVoiceProperties->parent)) {
            $this->setNavigationVoiceParentsActive($parentVoiceProperties->parent);
        }
    }
    
    /**********
    * COOKIES *
    **********/
    
    /**
    * Sets a cookie into current area cookies portion
    * @param string $propertyName: name of property to be set into area cookie
    * @param mixed $propertyValue: value of property to be set into area cookie
    */
    protected function setAreaCookie(string $propertyName, $propertyValue)
    {
        //set area cookie
        $this->cookie->setAreaCookie($this->area, $propertyName, $propertyValue);
        $areaCookie = $this->cookie->getAreaCookie($this->area);
        //update template parameter
        $this->setTemplateParameter('areaCookie', $areaCookie);
    }
    
    /**
    * Gets current area cookie as an object
    * @param string $propertyName: optional property yo be returned
    * @return object the whole area cookie
    */
    protected function getAreaCookie(string $propertyName = null)
    {
        return $this->cookie->getAreaCookie($this->area, $propertyName);
    }
}
