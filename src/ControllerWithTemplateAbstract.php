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
use function Simplex\PSR1NameToSlug;

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
        //parent jobs
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
    private function buildCommonTemplateHelpers()
    {
        /********
        * DEBUG *
        ********/
        //dumps var in development environment
        $this->addTemplateFunction(
            'dump',
            function($var){
                x($var);
            },
            ['is_safe' => ['html']]
        );
        /************
        * VARIABLES *
        ************/
        //changes an array or object property
        $this->addTemplateFunction('updateProperty', function($input, $property, $value) {
            if(is_array($input)) {
                $input[$property] = $value;
            } elseif (is_object($input)) {
                $input->$property = $value;
            }
            return $input;
        });
        //deletes an array or object property
        $this->addTemplateFunction('deleteProperty', function($input, $property) {
            if(is_array($input)) {
                unset($input[$property]);
            } elseif (is_object($input)) {
                unset($input->$property);
            }
            return $input;
        });
        //casts object to array (to iterate)
        $this->addTemplateFilter('objectToArray', function($object): array{
            return (array) $object;
        });
        //wrapper for vsprintf(), formats a string with an array of arguments
        $this->addTemplateFilter('formatArray', function(string $format, $args): string{
            if(is_array($args) && !empty($args)) {
                return vsprintf($format, $args);
            } else {
                return $format;
            }
        });
        //outputs a variable to be used in javascript context
        $this->addTemplateFilter('varToJs', function($var) {
            /*if(is_bool($var)) {
                return $var ? 'true' : 'false';
            } elseif(is_string($var)) {
                return $var ? 'true' : 'false';
            }*/
            return json_encode($var);
        },
        ['is_safe' => ['html']]);
        //convert metric byte units
        $this->addTemplateFunction('convertByteUnit', function(float $value, string $fromUnit, string $toUnit): string{
            return \ByteUnits\parse(sprintf('%s%s', $value, $fromUnit))->format($toUnit, ' ');
        });
        /********
        * PATHS *
        ********/
        //checks hether a file path is valid (is_file wrapper)
        $this->addTemplateFunction('isFile', function(string $path): bool{
            //remove trailing slash
            if(strpos($path, '/') === 0) {
                $path = substr($path, 1);
            }
            return is_file($path);
        });
        //returns path to yarn packages asset
        $this->addTemplateFilter('pathToNpmAsset', function(string $path){
            return sprintf('/%s/node_modules/%s', PUBLIC_SHARE_DIR, $path);
        });
        //checks whether a given path is the requested URI path
        //returns path to yarn packages asset
        $this->addTemplateFilter('isNavigationRouteCurrentRoute', function($path){
            return $this->isNavigationRouteCurrentRoute($path);
        });
        /********
        * DATES *
        ********/
        /* formats a date with locale awareness
        * @param string $date: in Y-m-d format
        * @param string $format: as accepted by strftime https://php.net/strftime
        */
        $this->addTemplateFunction('dateLocale', function(string $date, string $format){
            $date = \DateTime::createFromFormat('Y-m-d', $date);
            $timestamp = (int) $date->format('U');
            return strftime($format, $timestamp);
        });
        /*******
        * FORM *
        *******/
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
        //formats an id to be used by javascript
        $this->addTemplateFilter('formatIdforJs', function(string $id): string {
            return str_replace(['[',']'], '_', $id);
        });
        /********
        * USERS *
        ********/
        //checks a user permission
        $this->addTemplateFunction('checkPermission', function(string $permission){
            return $this->checkPermission($permission);
        });
    }
    
    /**
    * Build common template helpers going up the inheritance chain, used to generate templates cache during translations extraction
    */
    protected function buildTemplateHelpersBack()
    {
        $this->buildCommonTemplateHelpers();
    }
    
    /**
    * Sets common template parameters
    */
    private function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('environment', ENVIRONMENT);
        $this->setTemplateParameter('brand', BRAND);
        $this->setTemplateParameter('area', $this->area);
        $this->setTemplateParameter('action', $this->action);
        $this->setTemplateParameter('language', $this->language);
        $this->setTemplateParameter('languages', $this->languages);
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
    * Renders template and injects HTML code into response
    * @param string $templatePath: if null, into current namespace will be searched into 'templates' subfolder a template named after $this->action
    */
    protected function renderTemplateCode(string $templatePath = null): string
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
        return $this->template->render($templatePath, $this->templateParameters);
    }
    
    /**
    * Renders template and injects HTML code into response
    * @param string $templatePath: if null, into current namespace will be searched into 'templates' subfolder a template named after $this->action
    */
    protected function renderTemplate(string $templatePath = null)
    {
        $html = $this->renderTemplateCode($templatePath);
        //send HTML to response
        $response = $this->response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write($html);
    }
    
    /*************
    * NAVIGATION *
    *************/
    
    /**
    * Checks whether a given navigation route corresponds to the current route
    * @param string $path
    */
    protected function isNavigationRouteCurrentRoute(string $route)
    {
        //replace route placeholder (which will be substituted by record fields values) with regexp pattern
        $textPattern = '[0-9a-z-_]';
        $patterns = [
            sprintf('~{%s+}~', $textPattern)
        ];
        $replacements = [
            sprintf('%s+', $textPattern)
        ];
        $routePattern = sprintf('~%s~', preg_replace($patterns , $replacements , $route));
        //match the route stripped of placeholders against current one
        $matches = preg_match($routePattern, $this->request->getUri()->getPath());
        return $matches;
    }
    
    /**
     * Loads area navigation from a file
     * @param string $path: pat to file which return an array of navigations
     */
    protected function loadNavigation(string $path)
    {
        $navigations = require $path;
        foreach ($navigations as $navigationName => &$navigation) {
            $this->loadNavigationLevel($navigationName, $navigation);
        }
        $this->navigations = array_merge($this->navigations, $navigations);
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
            if(isset($voiceProperties->route) && $this->isNavigationRouteCurrentRoute($voiceProperties->route)) {
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
