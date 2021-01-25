<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Simplex\Controller\ControllerWithTemplateAbstract;

use Psr\Http\Message\ServerRequestInterface;

use function Simplex\getInstanceNamespace;
use function Simplex\PSR1NameToSlug;

/*
* Extends the ControllerAbstract class adding CRUDL (https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) funcionalities:
* - handling of list, create-form, update-form, delete-form, create, update and delete actions
* - requires route to pass a 'model' parameter, a class with the same name will be looked for in current namespace
*/
abstract class ControllerWithoutCRUDLAbstract extends ControllerWithTemplateAbstract
{
    /**
    * @var string
    * subject of the controller
    */
    protected $subject;

    /**
    * @param string
    * current route root till subject (included)
    **/
    protected $currentSubjectRoot;

    /**
    * @var mixed
    * model passed by route
    */
    protected $model;
    
    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        //Parent jobs
        parent::doBeforeActionExecution($request);
        //build common template helpers
        $this->buildCommonTemplateHelpers();
        //load navigation
        if($this->isAuthenticated()) {
            //area navigation which is *always* needed for ERP 
            $this->loadAreaNavigation();
        }
    }
    
    /**
    * Stores subject
    */
    protected function storeSubject()
    {
        $this->subject = $this->routeParameters->subject;
    }

    /**
     * Stores current route subject root
     */
    protected function storeCurrentSubjectRoot()
    {
        $currentRoute = $this->request->getUri()->getPath();
        $pattern = sprintf('~^[0-9a-zA-Z-_/]*/%s/?~', $this->subject);
        preg_match($pattern , $currentRoute, $matches);
        //remove ending slash
        $this->currentSubjectRoot = substr($matches[0], 0, -1);
    }
    
    /**
     * Stores model searching for a subject-namespace\Model class
     */
    protected function storeModel()
    {
        $nameSpace = explode('\\', getInstanceNamespace($this));
        $classSlug = PSR1NameToSlug(array_pop($nameSpace));
        $modelClassKey = sprintf('%s-model', $classSlug);
        //if model class has been defined into subject di-container config file load it
        if($this->DIContainer->has($modelClassKey)) {
            $this->model = $this->DIContainer->get($modelClassKey);
        }
    }
    
    /**
    * Build common template helpers
    * @param string $routePattern
    * @param  object $record
    */
    protected function parseRecordActionRoute(string $routePattern, object $record)
    {
        //get route pattern placeholders
        preg_match_all('/\{([a-z0-9_]+)\}/', $routePattern, $placeholders);
        $placeholders = $placeholders[1];
        //loop placeholders to find replacements
        $replacements = [];
        foreach ($placeholders as $placeholderIndex => $placeholder) {
            $placeholders[$placeholderIndex] = sprintf('/{(%s)}/', $placeholder);
            //placeholder value found
            if(isset($record->$placeholder)) {
                $replacements[$placeholderIndex] = $record->$placeholder;
                continue;
            }
            //default placeholder value is null
            $replacements[$placeholderIndex] = null;
        }
        $route = preg_replace($placeholders, $replacements, $routePattern);
        return $route;
    }
    
    /**
    * Builds common template helpers
    */
    protected function buildCommonTemplateHelpers()
    {
        /*************
        * NAVIGATION *
        *************/
        //gets a local controller navigations object
        $this->addTemplateFunction('getNavigations', function(ControllerWithTemplateAbstract $controller){
            $controller->loadSubjectNavigation();
            return $controller->getNavigations();
        });
        //parses a record action route pattern replacing placeholders with record values
        $this->addTemplateFunction(
            'parseRecordActionRoute',
            function(string $routePattern, object $record){
                /*//get route pattern placeholders
                preg_match_all('/\{([a-z0-9_]+)\}/', $routePattern, $placeholders);
                $placeholders = $placeholders[1];
                //loop placeholders to find replacements
                $replacements = [];
                foreach ($placeholders as $placeholderIndex => $placeholder) {
                    $placeholders[$placeholderIndex] = sprintf('/{(%s)}/', $placeholder);
                    //placeholder value found
                    if(isset($record->$placeholder)) {
                        $replacements[$placeholderIndex] = $record->$placeholder;
                        continue;
                    }
                    //default placeholder value is null
                    $replacements[$placeholderIndex] = null;
                }
                $route = preg_replace($placeholders, $replacements, $routePattern);
                return $route;*/
                return $this->parseRecordActionRoute($routePattern, $record);
            }
        );
        /*********
        * LABELS *
        *********/
        //builds an ancestor label
        $this->addTemplateFunction(
            'buildAncestorRecordLabel',
            function(string $subjectKey): string{
                $ancestor = $this->ancestors[$subjectKey];
                $CRUDLConfig = $ancestor->controller->getCRUDLConfig();
                $label = '';
                if(isset($CRUDLConfig->labelTokens)) {
                    $labelTokens = [];
                    foreach ((array) $CRUDLConfig->labelTokens as $token) {
                        $labelTokens[] = isset($ancestor->record->$token) ? (is_array($ancestor->record->$token) ? $ancestor->record->$token[$this->language->{'ISO-639-1'}] : $ancestor->record->$token) : $token;
                    }
                    $label = implode('', $labelTokens);
                }
                return $label;
            }
        );
        /*********
        * ALERTS *
        *********/
        //resets subject alerts
        $this->addTemplateFunction(
            'resetSubjectAlerts',
            function(){
                return $this->resetSubjectAlerts();
            }
        );
    }
}
