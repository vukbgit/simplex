<?php
declare(strict_types=1);

namespace Simplex\Controller;
//parent
use Simplex\Controller\ControllerAbstract;
//contructor injections
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Simplex\VanillaCookieExtended;
//other classes and functions
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Simplex\slugToPSR1Name;
use function Simplex\PSR1NameToSlug;
use function Simplex\getInstancePath;

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
    * @var object
    * labels containers
    */
    protected $labels;

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
        $this->labels = (object) [
            'actions' => (object) [],
            'alerts' => (object) [],
            'table' => (object) []
        ];
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
        * DATES *
        ********/
        /* formats a date with locale awareness
        */
        $this->addTemplateFunction('dateLocale', function(string $date, string $format): string{
            $date = \DateTime::createFromFormat('Y-m-d', $date);
            $timestamp = (int) $date->format('U');
            return strftime($format, $timestamp);
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
        //gets path to a local controller or model instance template
        $this->addTemplateFunction('getInstanceTemplate', function($instance, $templateName){
            if($instance == null) {
                $instance = $this;
            }
            return sprintf(
                '@local/%s/%s/%s',
                substr(getInstancePath($instance), strlen(PRIVATE_LOCAL_DIR) + 1),
                TEMPLATES_DEFAULT_FOLDER,
                $templateName
            );
        });
        //processes a template for a file upload preview
        $this->addTemplateFunction(
            'getPublicOutputFilePath',
            function($uploadKey, $outputKey, $fileName){
                return $this->model->getPublicOutputFilePath($uploadKey, $outputKey, $fileName);
            }
        );
        //processes a template for a file upload preview
        $this->addTemplateFunction(
            'formatUploadPreviewTemplate',
            function($uploadKey, $fileName, $previewTemplate){
                //file name
                $previewTemplate = preg_replace(
                    '/@name/',
                    sprintf('%s', $fileName),
                    $previewTemplate
                );
                //get field output keys
                foreach ($this->model->getUploadKeyOutputs($uploadKey) as $outputKey) {
                    $previewTemplate = preg_replace(
                        sprintf('/@%s/', $outputKey),
                        sprintf('%s', $this->model->getPublicOutputFilePath($uploadKey, $outputKey, $fileName)),
                        $previewTemplate
                    );
                }
                return $previewTemplate;
            },
            ['is_safe' => ['html']]
        );
        /*********
        * LOCALE *
        *********/
        /* get currently selected language locale
        * @param string $key: a specific property key of the language object to be returned
        */
        $this->addTemplateFunction('getLanguage', function(string $key = null){
            if($key) {
                return $this->language->$key;
            } else {
                return $this->language;
            }
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
        /*********
        * LABELS *
        *********/
        //sets a label by category and key
        $this->addTemplateFunction(
            'setLabel',
            function(string $category, string $key, $value): object {
                $this->labels->$category->$key = $value;
                return $this->labels;
            }
        );
        //sets a group of labels of one category at once
        $this->addTemplateFunction(
            'setLabels',
            function(string $category, array $labels): object {
                $this->labels->$category = (object) array_merge((array) (isset($this->labels->$category) ? $this->labels->$category : []), $labels);
                return $this->labels;
            }
        );
        //gets a label by category and key
        /*$this->addTemplateFunction(
            'getLabel',
            function($labels, $subject, $type, $key = null){
                $label = $labels->{$subject}[$type] ?? '';
                if($key) {
                    $label = $label[$key] ?? '';
                }
                return $label;
            }
        );*/
        $this->addTemplateFunction(
            'getLabel',
            /**
            * first parameter is category, others are nested keys
            **/
            function() {
                $arguments = func_get_args();
                $category = array_shift($arguments);
                $result = $this->labels->$category;
                foreach($arguments as $key) {
                    $result = is_array($result->$key) ? (object) $result->$key : $result->$key;
                }
                return $result;
            }
        );
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
        $this->setTemplateParameter('labels', $this->labels);
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
        if(!$route) {
            return false;
        }
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
     * Loads area navigation
     */
    protected function loadAreaNavigation()
    {
        //check path
        if(!defined('AREA_NAVIGATION_PATH') || !is_file(AREA_NAVIGATION_PATH)) {
            throw new \Exception('constant AREA_NAVIGATION_PATH *MUST* be defined for current area and must be a valid path');
        }
        //load navigation
        $this->loadNavigation(AREA_NAVIGATION_PATH);
        //check that there is one navigation named 'area'
        if(!isset($this->navigations['area'])) {
            throw new \Exception('There MUST be a loaded navigation named \'area\'');
        }
    }
    
    /**
     * Gets navigations
     */
    public function getNavigations()
    {
        return $this->navigations;
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
            //check voice permission (only if controller has been invoked by router and so request is defined)
            if(isset($this->request) && isset($voiceProperties->permissions) && !$this->checkAtLeastOnePermission($voiceProperties->permissions)) {
                unset($loadedNavigationLevel[$voiceKey]);
                continue;
            }
            if($parentVoiceProperties) {
                //$voiceProperties->parent = $parentVoiceProperties;
            }
            //check if its current route (only if controller has been invoked by router and so request is defined)
            $route = isset($voiceProperties->route) ? $voiceProperties->route : (isset($voiceProperties->routeFromSubject) ? $this->buildRouteToActionFromRoot($voiceProperties->routeFromSubject) : null);
            if(isset($this->request) && isset($route) && $this->isNavigationRouteCurrentRoute($route)) {
                $voiceProperties->isActive = true;
                $this->currentNavigationVoice[$navigationName] = $voiceKey;
                if($parentVoiceProperties) {
                    $this->setNavigationVoiceParentsActive($parentVoiceProperties);
                }
            }
            //check sub level
            if(isset($voiceProperties->navigation) && !empty($voiceProperties->navigation)) {
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
    
    /********
    * FORMS *
    ********/
    
    /**
    * Processes a recordset to be used in a radio, checkbox or select mapping fields to value and label
    * @param string $valueField
    * @param mixed $labelTokens: the neme of one field or an array of fields names and strings to be joined to form label
    * @param array $recordset
    * @param string $languageCode: optional language code to use for localized fields
    * @return array of records
    */
    protected function processRecordsetForInput(string $valueField, $labelTokens, array $recordset, string $languageCode = null): array
    {
        //check labelfields and turn into an array if it's a string
        if(is_string($labelTokens)) {
            $labelTokens = [$labelTokens];
        }
        //check language code
        if(!$languageCode) {
            $languageCode = $this->language->{'ISO-639-1'};
        }
        $items = [];
        foreach ((array) $recordset as $record) {
            $label = '';
            foreach ($labelTokens as $labelToken) {
                //dealing with a record field
                if(isset($record->$labelToken)) {
                    //localized field
                    if(is_array($record->$labelToken)) {
                        $tokenLabel = $record->$labelToken[$languageCode];
                    } else {
                    //not a localized field
                        $tokenLabel = $record->$labelToken;
                    }
                } else {
                //it's a text token, take it as it is
                    $tokenLabel = $labelToken;
                }
                $label .= $tokenLabel;
            }
            $items[] = (object) [
                'value' => $record->$valueField,
                'label' => $label
            ];
        }
        return $items;
    }
}
