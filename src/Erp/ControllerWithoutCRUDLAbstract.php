<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Simplex\Controller\ControllerWithTemplateAbstract;

use Psr\Http\Message\ServerRequestInterface;

use function Simplex\getInstanceNamespace;
use function Simplex\getInstancePath;
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
    * @param object
    * subject config object
    **/
    protected $subjectConfig;

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
        //store subject
        $this->storeSubject();
        //build common template helpers
        $this->buildCommonTemplateHelpers();
        //load navigation
        if($this->isAuthenticated()) {
            //area navigation which is *always* needed for ERP 
            $this->loadSubjectConfig();
            $this->loadAreaNavigation($this->request);
            $this->checkActionPermission();
        }
        //set template parameters
        $this->setCommonTemplateParameters();
    }
    
    /**
    * Stores subject
    */
    protected function storeSubject()
    {
      if(isset($this->routeParameters->subject)) {
        $this->subject = $this->routeParameters->subject;
      }
    }

    /**
     * Stores current route subject root
     */
    protected function storeCurrentSubjectRoot()
    {
        $currentRoute = $this->request->getUri()->getPath();
        //$pattern = sprintf('~^[0-9a-zA-Z-_/]*/%s/?~', $this->subject);
        //slash after subject name should always be present (followed by action), if it's considered optional as above actions containing subject name will false result
        $pattern = sprintf('~^[0-9a-zA-Z-_/]*/%s/~', $this->subject);
        preg_match($pattern , $currentRoute, $matches);
        //remove ending slash
        if(isset($matches[0])) {
            $this->currentSubjectRoot = substr($matches[0], 0, -1);
        }
    }
    
    /**
     * Loads subject config which is not (yet) mandatory for ERP
     * TODO should hold actions informations currently stored into navigation configuration
     */
    protected function loadSubjectConfig()
    {
        //config file must be into class-folder/config/subject.php
        $configPath = sprintf('%s/config/subject.php', getInstancePath($this));
        //check path
        if(is_file($configPath)) {
            //store config
            $this->subjectConfig = require $configPath;
        }
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
     * Loads subject navigation which is *always* needed for ERP 
     */
    private function checkActionPermission()
    {
      //check free action
      if(isset($this->subjectConfig->actions[$this->action]->free) && $this->subjectConfig->actions[$this->action]->free) {
        return;
      //check action level permissions
      } elseif(isset($this->subjectConfig->actions[$this->action]->permissions)) {
          if(!$this->checkAtLeastOnePermission($this->subjectConfig->actions[$this->action]->permissions)) {
              throw new \Exception("Specific permissions have been set for current subject action but current user has none of them", 1);
          }
      //check subject level permissions
      } elseif(isset($this->subjectConfig->subjectPermissions)) {
          if(!$this->checkAtLeastOnePermission($this->subjectConfig->subjectPermissions)) {
              throw new \Exception("Global permissions have been set for current subject but current user has none of them", 1);
          }
      }
    }
    
    /**
    * Parses a route which refers to a record
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
    * Sets common template parameters
    */
    protected function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('subject', $this->subject);
        $this->setTemplateParameter('userData', $this->getAuthenticatedUserData());
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
            $controller->loadSubjectNavigation($this->request);
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
        //builds an action label
        $this->addTemplateFunction(
            'getActionLabel',
            function($actionKey) {
              //search into subject
              $label = $this->getLabel($this->subject, 'actions', $actionKey) ?? $this->getLabel('actions', $actionKey);
              return $label;
            }
        );
        //builds an alert label
        $this->addTemplateFunction(
            'getAlertLabel',
            function($alertCode) {
              //search into subject
              $label = $this->getLabel($this->subject, 'alerts', $alertCode) ?? $this->getLabel('alerts', $alertCode);
              return $label;
            }
        );
        //builds an ancestor label
        $this->addTemplateFunction(
            'buildAncestorRecordLabel',
            function(string $subjectKey): string{
                $ancestor = $this->ancestors[$subjectKey];
                $CRUDLConfig = $ancestor->controller->getCRUDLConfig();
                $label = '';
                if(isset($CRUDLConfig->labelTokens)) {
                    $label = $this->buildRecordTokensLabel($CRUDLConfig->labelTokens, $ancestor->record);
                }
                return $label;
            }
        );
        //builds a record token label
        $this->addTemplateFunction(
            'buildRecordTokenLabel',
            function(string $subjectKey, object $record): string{
                $controller = $this->DIContainer->get(sprintf('%s-controller', $subjectKey));
                $CRUDLConfig = $controller->getCRUDLConfig();
                $label = '';
                if(isset($CRUDLConfig->labelTokens)) {
                    $label = $this->buildRecordTokensLabel($CRUDLConfig->labelTokens, $record);
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
    
    /**
     * Sets sidebar state, ERP subjects routes contain matching route by default
     */
    protected function setSideBarState()
    {
      $state = filter_input(
        INPUT_GET,
        's',
        FILTER_VALIDATE_REGEXP,
        [
          'options' => [
            'regexp'=>'/^[0-1]{1}$/'
          ]
        ]
      );
      if($state !== false) {
        $this->setAreaCookie('sideBarState', $state);
      }
    }
    
}
