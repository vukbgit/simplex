<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\ControllerWithTemplateAbstract;
use function Simplex\getInstanceNamespace;
use function Simplex\getInstancePath;

/*
* Extends the ControllerAbstract class adding CRUDL (https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) funcionalities:
* - handling of list, create-form, update-form, delete-form, create, update and delete actions
* - requires route to pass a 'model' parameter, a class with the same name will be looked for in current namespace
*/
abstract class ControllerAbstract extends ControllerWithTemplateAbstract
{
    /**
    * @var string
    * subject of the controller
    */
    protected $subject;
    
    /**
    * @var mixed
    * model passed by route
    */
    protected $model;

    /**
    * @param object
    * CRUDL config object
    **/
    private $CRUDLConfig;
    
    /**
    * @param object
    * current user options, set by the UI and stored into area cookie under subject property
    **/
    private $subjectCookie;
    
    /**
    * @param string
    * current route root till subject (included9)
    **/
    private $currentRouteSubjectRoot;
    
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
        //store current route subject root
        $this->storeCurrentRouteSubjectRoot();
        //store model
        $this->storeModel();
        //load ERP config
        $this->loadCRUDLConfig();
        //get cookie stored user options
        $this->getSubjectCookie();
        //load navigation
        $this->loadAreaNavigation();
        $this->loadSubjectNavigation();
        //build common template helpers
        $this->buildCommonTemplateHelpers();
        //set specific CRUDL template parameters
        $this->setCommonTemplateParameters();
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
    protected function storeCurrentRouteSubjectRoot()
    {
        $currentRoute = $this->request->getUri()->getPath();
        $pattern = sprintf('~^[0-1a-zA-Z/]+/%s~', $this->subject);
        preg_match($pattern , $currentRoute, $matches); 
        $this->currentRouteSubjectRoot = $matches[0];
    }
    
    /**
     * Builds the route to an action from current route subject root
     * @param string $actionRoutePart: the last part of the route wit action name and optional other parameters (such as primary key value)
     * @return string the built route
     */
    protected function buildRouteToActionFromRoot(string $actionRoutePart): string
    {
        return sprintf('%s/%s', $this->currentRouteSubjectRoot, $actionRoutePart);
    }

    /**
     * Stores model searching for a subject-namespace\Model class
     */
    protected function storeModel()
    {
        $this->model = $this->DIContainer->get(sprintf('%s-model', $this->subject));
    }
    
    /**
     * Loads CRUDL config which is mandatory for ERP and contains informations for the CRUDL interface to be exposed
     */
    protected function loadCRUDLConfig()
    {
        //config file must be into class-folder/config/model.php
        $configPath = sprintf('%s/config/crudl.php', getInstancePath($this));
        //check path
        if(!is_file($configPath)) {
            throw new \Exception(sprintf('configuration CRUDL file \'%s\' for subject %s is not a valid path', $configPath, $this->subject));
        }
        //store config
        $this->CRUDLconfig = require $configPath;
    }
    
    /**********
    * OPTIONS *
    **********/
    
    /**
     * Gets user options stored into cookie
     */
    protected function getSubjectCookie(): object
    {
        $this->subjectCookie = $this->getAreaCookie()->{$this->subject} ?? new \stdClass;
        return $this->subjectCookie;
    }

    /**
     * Sets an subject information to be stored into cookie
     * @param string $key
     * @param mixed $value
     */
    protected function setSubjectCookie(string $key, $value)
    {
        $this->subjectCookie->$key = $value;
        $this->setAreaCookie($this->subject, $this->subjectCookie);
    }

    /**
     * Sets a feedback categorized alert into subject cookie
     * @param string $severity: one of Bootstrap alert suffixes (https://getbootstrap.com/docs/4.3/components/alerts)
     * @param object $alert: object with alert informations:
     *   ->code: alphanumeric message code to be searched for into template alerts texts container
     *   ->data: an array with any specific error code relevant data (such as involved field names)
     */
    protected function setSubjectAlert(string $severity, object $alert)
    {
        //init messages
        if(!isset($this->subjectCookie->alerts)) {
            $this->subjectCookie->alerts = [];
        } else {
            //json_decode turn associative array into objects
            $this->subjectCookie->alerts = (array) $this->subjectCookie->alerts;
        }
        //init context messages
        if(!isset($this->subjectCookie->alerts[$severity])) {
            $this->subjectCookie->alerts[$severity] = [];
        }
        //set message
        $this->subjectCookie->alerts[$severity][] = $alert;
        //store into subject cookie
        $this->setSubjectCookie('alerts', $this->subjectCookie->alerts);
    }

    /**
     * Resests categorized alerts into subject cookie
     */
    protected function resetSubjectAlerts()
    {
        $this->setSubjectCookie('alerts', null);
    }

    /**
     * Sets a feedback categorized alert into subject cookie taking an exception message
     * @param \Exception $exception
     */
    protected function setSubjectAlertFromException(\Exception $exception)
    {
        $this->setSubjectAlert('danger', $exception->getMessage());
    }

    /**
     * Loads area navigation which is *always* needed for ERP 
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
    
    /*************
    * NAVIGATION *
    *************/
    /**
     * Loads subject navigation which is *always* needed for ERP 
     */
    protected function loadSubjectNavigation()
    {
        //config file must be into class-folder/config/model.php
        $configPath = sprintf('%s/config/navigation.php', getInstancePath($this));
        //check path
        if(!is_file($configPath)) {
            throw new \Exception(sprintf('configuration file \'%s\' for model navigation %s is not a valid path', $configPath, getInstanceNamespace($this)));
        }
        //load navigation
        $this->loadNavigation($configPath);
    }
    
    /**
     * Loads actions definitions which are *always* needed for ERP 
     */
    protected function loadActions()
    {
        //config file must be into class-folder/config/actions.php
        $configPath = sprintf('%s/config/actions.php', getInstancePath($this));
        //check path
        if(!is_file($configPath)) {
            throw new \Exception(sprintf('Actions configuration file \'%s\' for %s is not a valid path', $configPath, getInstanceNamespace($this)));
        }
    }

    /***********
    * TEMPLATE *
    ***********/
    
    /**
    * Build common template helpers
    */
    private function buildCommonTemplateHelpers()
    {
        //builds route to an action
        $this->addTemplateFunction(
            'buildRouteToActionFromRoot',
            function(string $actionRoutePart){
                return $this->buildRouteToActionFromRoot($actionRoutePart);
            }
        );
        //parses a record action route pattern replacing placeholders with record values
        $this->addTemplateFunction(
            'parseRecordActionRoute',
            function(string $routePattern, object $record){
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
        );
        //resets subject alerts
        $this->addTemplateFunction(
            'resetSubjectAlerts',
            function(){
                return $this->resetSubjectAlerts();
            }
        );
    }
    
    /**
    * Sets common template parameters
    */
    private function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('userData', $this->getAuthenticatedUserData());
        $this->setTemplateParameter('subject', $this->subject);
        $this->setTemplateParameter('model', $this->model);
        $this->setTemplateParameter('currentNavigationVoice', $this->currentNavigationVoice);
        $this->setTemplateParameter('sideBarClosed', $this->getAreaCookie('sideBarClosed') ?? false);
        $this->setTemplateParameter('pathToSubjectTemplate', sprintf('@local/%s/%s/subject.twig', str_replace('\\', '/', getInstanceNamespace($this, true)), TEMPLATES_DEFAULT_FOLDER));
    }
    
    /**************
    * LIST ACTION *
    **************/

    /**
     * Lists records
     */
    protected function list()
    {
        $parser = new \FastRoute\RouteParser\Std();
        $route = $parser->parse('/backend/{subject}/list');
        $route = $parser->parse('/backend/{subject}/insert-form');
        //check list query modifiers
        $this->setListQueryModifiers();
        //get model list
        $records = $this->model->get(
            $this->buildListWhere(),
            $this->subjectCookie->sorting ?? []
        );
        //x($this->model->sql());
        $this->setTemplateParameter('records', $records);
        //render
        $this->renderTemplate(sprintf(
            '@local/%s/%s/list.%s',
            getInstanceNamespace($this, true),
            TEMPLATES_DEFAULT_FOLDER,
            TEMPLATES_EXTENSION
        ));
    }
    
    /**
     * Checks information passed by list view that modifies model query: sorting, filtering and pagination
     */
    protected function setListQueryModifiers()
    {
        //get input
        $fieldsDefinitions = [
            'modifier' => FILTER_SANITIZE_STRING,
            'field' => FILTER_SANITIZE_STRING,
            //sorting
            'direction' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => array('regexp'=>'/asc|ASC|des|DESC/')
            ],
            //filter
            'filter' => FILTER_SANITIZE_STRING
        ];
        $input = filter_input_array(INPUT_POST, $fieldsDefinitions);
        if($input) {
            $input = (object) $input;
            switch ($input->modifier) {
                //sorting
                case 'sort':
                    $this->replaceListSort([[$input->field, strtoupper($input->direction)]]);
                break;
                //filter
                case 'filter':
                    $this->replaceListFilter($input->filter);
                break;
            }
        }
    }
    
    /**
    * Replaces current sorting
    * @param array $sorting: associative array of directions indexed by fields
    */
    public function replaceListSort(array $sorting)
    {
        $this->setSubjectCookie('sorting', $sorting);
    }
    
    /**
    * Replaces current filter
    * @param string $filter
    */
    public function replaceListFilter(string $filter)
    {
        $this->setSubjectCookie('filter', $filter);
    }
    
    /**
    * Builds list query where based on modifiers
    * @return array as accepted by Pixie query builder
    */
    private function buildListWhere(): array
    {
        $where = [];
        //filter
        $subjectCookie = $this->getSubjectCookie();
        if(isset($subjectCookie->filter)) {
            //loop config fields
            foreach ((array) $this->CRUDLconfig->fields as $fieldName => $fieldConfig) {
                if(!isset($fieldConfig->tableFilter) || $fieldConfig->tableFilter) {
                    $where[] = [$fieldName, 'LIKE', sprintf('%%%s%%', $subjectCookie->filter)];
                }
            }
        }
        return $where;
    }
    
    /***************
    * CRUDL ACTIONS *
    ***************/

    /**
     * Gets model record t ooperate on by route primary key value
     */
    protected function getModelRecordFromRoute()
    {
        //get primary key fields
        $primaryKeys = $this->model->getConfig()->primaryKey;
        $where = [];
        foreach($primaryKeys as $primaryKey) {
            $where[] = [$primaryKey, $this->routeParameters->$primaryKey];
        }
        return $this->model->first($where);
    }
    
    /**
     * Insert form
     */
    protected function insertForm()
    {
        //render
        $this->renderTemplate(sprintf(
            '@local/%s/%s/crudl-form.%s',
            getInstanceNamespace($this, true),
            TEMPLATES_DEFAULT_FOLDER,
            TEMPLATES_EXTENSION
        ));
    }
    
    /**
     * Update form
     */
    protected function updateForm()
    {
        //get model record
        $this->setTemplateParameter('record', $this->getModelRecordFromRoute());
        //render
        $this->renderTemplate(sprintf(
            '@local/%s/%s/crudl-form.%s',
            getInstanceNamespace($this, true),
            TEMPLATES_DEFAULT_FOLDER,
            TEMPLATES_EXTENSION
        ));
    }
    
    /**
     * Delete form
     */
    protected function deleteForm()
    {
        //get model record
        $this->setTemplateParameter('record', $this->getModelRecordFromRoute());
        //render
        $this->renderTemplate(sprintf(
            '@local/%s/%s/crudl-form.%s',
            getInstanceNamespace($this, true),
            TEMPLATES_DEFAULT_FOLDER,
            TEMPLATES_EXTENSION
        ));
    }
    
    /**
     * Processes save form input to manipulate fields data before saving
     * this method is void by defaulkt, it must be overridden by derived class if necessary
     */
    protected function processSaveFormInput(&$input)
    {
    }
    
    /**
     * Purges input array from primary key values and return them into an array
     * this method is void by defaulkt, it must be overridden by derived class if necessary
     */
    protected function extractPrimaryKeyValuesFromInput(&$input)
    {
        $primaryKeyFields = $this->model->getConfig()->primaryKey;
        $primaryKeyValues = [];
        foreach ($primaryKeyFields as $primaryKeyField) {
            $primaryKeyValues[$primaryKeyField] = $input[$primaryKeyField];
            unset($input[$primaryKeyField]);
        }
        return $primaryKeyValues;
    }
    
    /**
     * Gets save form input
     * @return object with properties:
     *      ->primaryKeyValues: array with values of primary key(s) fields indexed by primary key fields names
     *      ->saveFieldsValues: array with values of fields to be saved indexed by fields names
     */
    protected function getSaveFieldsData(): object
    {
        //get fields filters
        $inputFieldsFilters = array_filter(
            //extract filter definition from fields config
            array_map(
                function($fieldConfiguration) {
                    return $fieldConfiguration->inputFilter ?? null;
                },
                $this->CRUDLconfig->fields
            ),
            //keep only fields with a not null filter definition
            function($fieldFilter) {
                return $fieldFilter;
            }
        );
        //get input
        $input = filter_input_array(INPUT_POST, $inputFieldsFilters);
        //process input
        $this->processSaveFormInput($input);
        //get primary key and purge it from input values
        $primaryKeyValues = $this->extractPrimaryKeyValuesFromInput($input);
        return (object) [
            'primaryKeyValues' => $primaryKeyValues,
            'saveFieldsValues' => $input
        ];
    }
    
    /**
     * Inserts record
     */
    protected function insert()
    {
        $fieldsData = $this->getSaveFieldsData();
        try {
            //save record
            $this->model->insert($fieldsData->saveFieldsValues);
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            $this->setSubjectAlert('success', (object) ['code' => 'save_success']);
        } catch(\PDOException $exception) {
            $error = $this->model->handleException($exception);
            $this->setSubjectAlert('danger', $error);
            $redirectRoute = $this->buildRouteToActionFromRoot('insert-form');
        }
        //redirect
        $this->redirect($redirectRoute);
    }
    
    /**
     * Updates record
     */
    protected function update()
    {
        $fieldsData = $this->getSaveFieldsData();
        try {
            //save record
            $this->model->update($fieldsData->primaryKeyValues, $fieldsData->saveFieldsValues);
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            $this->setSubjectAlert('success', (object) ['code' => 'save_success']);
        } catch(\PDOException $exception) {
            $error = $this->model->handleException($exception);
            $this->setSubjectAlert('danger', $error);
            $redirectRoute = $this->buildRouteToActionFromRoot(sprintf('update-form/%s', implode('/', array_values($primaryKeyValues))));
        }
        //redirect
        $this->redirect($redirectRoute);
    }
    
    /**
     * Deletes record
     */
    protected function delete()
    {
        $fieldsData = $this->getSaveFieldsData();
        try {
            //delete record
            $this->model->delete($fieldsData->primaryKeyValues);
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            $this->setSubjectAlert('success', (object) ['code' => 'delete_success']);
        } catch(\PDOException $exception) {
            $error = $this->model->handleException($exception);
            $this->setSubjectAlert('danger', $error);
            $redirectRoute = $this->buildRouteToActionFromRoot(sprintf('update-form/%s', implode('/', array_values($primaryKeyValues))));
        }
        //redirect
        $this->redirect($redirectRoute);
    }
};
