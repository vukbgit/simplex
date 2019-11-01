<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\Controller\ControllerWithTemplateAbstract;
use Spatie\Image\Image;
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
    * @var array
    * ancestors models passed by route
    */
    protected $ancestors = [];

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
    * current route root till subject (included)
    **/
    private $currentSubjectRoot;
    
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
        $this->storeCurrentSubjectRoot();
        //store model
        $this->storeModel();
        //store ancestors
        $this->storeAncestors();
        //load ERP config
        $this->loadCRUDLConfig();
        //get cookie stored user options
        $this->getSubjectCookie();
        //load navigation
        if($this->isAuthenticated()) {
            $this->loadAreaNavigation();
            $this->loadSubjectNavigation();
        }
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
    protected function storeCurrentSubjectRoot()
    {
        $currentRoute = $this->request->getUri()->getPath();
        $pattern = sprintf('~^[0-9a-zA-Z-_/]*/%s/~', $this->subject);
        preg_match($pattern , $currentRoute, $matches);
        //remove ending slash
        $this->currentSubjectRoot = substr($matches[0], 0, -1);
    }
    
    /**
     * Builds the route to an action from current route subject root
     * @param string $actionRoutePart: the last part of the route wit action name and optional other parameters (such as primary key value)
     * @return string the built route
     */
    protected function buildRouteToActionFromRoot(string $actionRoutePart): string
    {
        return sprintf('%s/%s', $this->currentSubjectRoot, $actionRoutePart);
    }

    /**
     * Stores model searching for a subject-namespace\Model class
     */
    protected function storeModel()
    {
        $modelClassKey = sprintf('%s-model', $this->subject);
        //if model class has been defined into subject di-container config file load it
        if($this->DIContainer->has($modelClassKey)) {
            $this->model = $this->DIContainer->get($modelClassKey);
        }
    }
    
    /**
     * Stores ancestors models searching for a ancestor-namespace\Model class
     */
    protected function storeAncestors()
    {
        //loop routeParameters
        foreach ($this->routeParameters as $parameter => $subjectKey) {
            if(substr($parameter, 0, 8) == 'ancestor') {
                //get the route fragment to this ancestor
                preg_match(sprintf('~^[0-9a-zA-Z-_/]*/%s/~', $subjectKey), $this->currentSubjectRoot, $matches);
                //suppose the main action to ancestor is list as it should
                $routeBack = sprintf('%slist', $matches[0]);
                $this->ancestors[$subjectKey] = (object) [
                    'controller' => $this->DIContainer->get(sprintf('%s-controller', $subjectKey)),
                    'model' => $this->DIContainer->get(sprintf('%s-model', $subjectKey)),
                    'routeBack' => $routeBack
                ];
            }
        }
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
    public function loadSubjectNavigation()
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
        /*************
        * NAVIGATION *
        *************/
        //gets a local controller navigationS object
        $this->addTemplateFunction('getNavigations', function(ControllerWithTemplateAbstract $controller){
            $controller->loadSubjectNavigation();
            return $controller->getNavigations();
        });
        
    }
    
    /**
    * Build common template helpers going up the inheritance chain, used to generate templates cache during translations extraction
    */
    protected function buildTemplateHelpersBack()
    {
        parent::buildTemplateHelpersBack();
        $this->buildCommonTemplateHelpers();
    }
    
    /**
    * Sets common template parameters
    */
    protected function setCommonTemplateParameters()
    {
        $this->setTemplateParameter('userData', $this->getAuthenticatedUserData());
        $this->setTemplateParameter('subject', $this->subject);
        $this->setTemplateParameter('model', $this->model);
        $this->setTemplateParameter('ancestors', $this->ancestors);
        $this->setTemplateParameter('currentNavigationVoice', $this->currentNavigationVoice);
        $this->setTemplateParameter('sideBarClosed', $this->getAreaCookie('sideBarClosed') ?? false);
    }
    
    /**************
    * LIST ACTION *
    **************/

    /**
     * Gets the recordset for the list
     */
    protected function getList()
    {
        return $this->model->get(
            $this->buildListWhere(),
            $this->subjectCookie->sorting ?? []
        );
    }
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
        $records = $this->getList();
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
    protected function buildListWhere(): array
    {
        $where = [];
        //filter
        $subjectCookie = $this->getSubjectCookie();
        if(isset($subjectCookie->filter) && $subjectCookie->filter) {
            //create a grouped where for the filter
            $filterWhere = [
                'grouped' => true
            ];
            //loop config fields
            foreach ((array) $this->CRUDLconfig->fields as $fieldName => $fieldConfig) {
                if(!isset($fieldConfig->tableFilter) || $fieldConfig->tableFilter) {
                    //filter fields conditions are joined by the logical OR operator
                    $filterWhere[] = [$fieldName, 'LIKE', sprintf('%%%s%%', $subjectCookie->filter), 'logical' => 'OR'];
                }
            }
            $where[] = $filterWhere;
        }
        //parent primary key
        if(!empty($this->ancestors)) {
            $parent = end($this->ancestors)->model;
            $parentConfig = $parent->getConfig();
            //check parent primary key into route
            if(!isset($this->routeParameters->{$parentConfig->primaryKey})) {
                throw new \Exception(sprintf('Controller %s has defined the ancestor %s into route but ancestor primary key %s value is not set into route', getInstanceNamespace($this), getInstanceNamespace($parent), $parentConfig->primaryKey));
                
            }
            $parentPrimaryKeyValue = $this->routeParameters->{$parentConfig->primaryKey};
            $where[] = [$parentConfig->primaryKey, $parentPrimaryKeyValue];
        }
        return $where;
    }
    
    /***************
    * CRUDL ACTIONS *
    ***************/

    /**
     * Gets model record to operate on by route primary key value
     */
    protected function getModelRecordFromRoute()
    {
        //get primary key fields
        $primaryKey = $this->model->getConfig()->primaryKey;
        $where = [[$primaryKey, $this->routeParameters->$primaryKey]];
        return $this->model->first($where);
    }
    
    /**
     * Gets any data necessary to the save form
     * to be overridden if necessary by derived classes
     */
    protected function getSaveFormData()
    {
        //uploads
        $modelConfig = $this->model->getConfig();
        if(isset($modelConfig->uploads)) {
            //max file size
            $uploadMaxFilesizeIni = ini_get('upload_max_filesize');
            $uploadMaxFilesizeBytes = bytes(ini_get('upload_max_filesize'));
            //in kB for client validation
            $uploadMaxFilesizeKB = number_format((float) str_replace('kB', '', \ByteUnits\bytes($uploadMaxFilesizeBytes)->format('kB')), 2, '.', '');
            //in MB to be displayed
            $uploadMaxFilesizeMB = str_replace('MB', '', \ByteUnits\bytes($uploadMaxFilesizeBytes)->format('MB'));
            $this->setTemplateParameter('uploadMaxFilesizeKB', $uploadMaxFilesizeKB);
            $this->setTemplateParameter('uploadMaxFilesizeMB', $uploadMaxFilesizeMB);
        }
    }
    
    /**
     * Insert form
     */
    protected function insertForm()
    {
        //pass fields config to template
        $this->setTemplateParameter('CRUDLconfig', $this->CRUDLconfig);
        //get any necessary data
        $this->getSaveFormData();
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
        //pass fields config to template
        $this->setTemplateParameter('CRUDLconfig', $this->CRUDLconfig);
        //get model record
        $this->setTemplateParameter('record', $this->getModelRecordFromRoute());
        //get any necessary data
        $this->getSaveFormData();
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
        //get any necessary data
        $this->getSaveFormData();
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
     * this method is void by default, it must be overridden by derived class if necessary
     * It operates over input by reference and can return any type of extra data that will be inserted into the result of getSaveFieldsData
     */
    protected function processSaveFormInput(&$input)
    {
    }
    
    /**
     * Purges input array from primary key value and returns it
     * this method is void by defaulkt, it must be overridden by derived class if necessary
     */
    protected function extractPrimaryKeyValueFromInput(&$input)
    {
        $primaryKeyField = $this->model->getConfig()->primaryKey;
        $primaryKeyValue = $input[$primaryKeyField];
        unset($input[$primaryKeyField]);
        return $primaryKeyValue;
    }
    
    /**
     * Gets save form input
     * @return object with properties:
     *      ->primaryKeyValue: primary key value
     *      ->saveFieldsValues: array with values of fields to be saved indexed by fields names
     */
    protected function getSaveFieldsData(): object
    {
        //extract filter definition from fields config
        //get fields filters
        $inputFieldsFilters = array_filter(
            //return true if field has a filter definition
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
        //deal with localized fields
        if($this->model->hasLocales()) {
            $modelLocales = $this->model->getConfig()->locales ?? [];
            foreach ($inputFieldsFilters as $fieldName => $inputFilter) {
                //localized field
                if(in_array($fieldName, $modelLocales)) {
                    if(!is_array($inputFilter)) {
                        $inputFilter = ['filter' => $inputFilter];
                    }
                    if(!isset($inputFilter['flags'])) {
                        $inputFilter['flags'] = 0;
                    }
                    $inputFilter['flags'] = FILTER_REQUIRE_ARRAY;
                    $inputFieldsFilters[$fieldName] = $inputFilter;
                }
            }
        }
        $input = filter_input_array(INPUT_POST, $inputFieldsFilters);
        //process input
        $inputExtraData = $this->processSaveFormInput($input);
        //separate localized and non localized values
        $inputLocales = [];
        foreach (array_keys((array) $this->languages) as $languageCode) {
            $inputLocales[$languageCode] = [];
        }
        if($this->model->hasLocales()) {
            foreach ($input as $fieldName => $fieldLocalesValues) {
                if(in_array($fieldName, $modelLocales)) {
                    if(is_array($fieldLocalesValues)) {
                        foreach ($fieldLocalesValues as $languageCode => $languageValue) {
                            $inputLocales[$languageCode][$fieldName] = $languageValue;
                        }
                    }
                    unset($input[$fieldName]);
                }
            }
        }
        //get primary key and purge it from input values
        $primaryKeyValue = $this->extractPrimaryKeyValueFromInput($input);
        //add eventual model upload fields
        $uploadsInput = null;
        if($this->model->hasUploads()) {
            $uploadsFilters = [];
            foreach ($this->model->getUploadKeys() as $uploadKey) {
                $uploadsFilters[$uploadKey] = [
                    'filter' => FILTER_VALIDATE_REGEXP,
                    'options' => ['regexp'=>'/^([0-9a-zA-z_ -]+\.[a-zA-Z]{3,4}\|?)+$/']
                ];
            }
            $uploadsInput = (object) filter_input_array(INPUT_POST, $uploadsFilters);
            
        }
        //get input
        return (object) [
            'primaryKeyValue' => $primaryKeyValue,
            'saveFieldsValues' => $input,
            'saveLocalesFieldsValues' => $inputLocales,
            'uploadsValues' => $uploadsInput,
            'extraData' => $inputExtraData
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
            $primaryKeyValue = $this->model->insert($fieldsData->saveFieldsValues);
            //save locales
            if($this->model->hasLocales()) {
                $this->model->saveLocales($primaryKeyValue, $fieldsData->saveLocalesFieldsValues);
            }
            //save uploads
            if($this->model->hasUploads()) {
                $this->model->saveUploadsFiles($primaryKeyValue, $fieldsData->uploadsValues);
            }
            //post save processing
            $this->doAfterRecordSave($primaryKeyValue, $fieldsData);
            //redirect
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            //message
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
            if(!empty($fieldsData->saveFieldsValues)) {
                $this->model->update($fieldsData->primaryKeyValue, $fieldsData->saveFieldsValues);
            }
            //save locales
            if($this->model->hasLocales()) {
                $this->model->saveLocales($fieldsData->primaryKeyValue, $fieldsData->saveLocalesFieldsValues);
            }
            //save uploads
            if($this->model->hasUploads()) {
                $this->model->saveUploadsFiles($fieldsData->primaryKeyValue, $fieldsData->uploadsValues);
            }
            //post save processing
            $this->doAfterRecordSave($fieldsData->primaryKeyValue, $fieldsData);
            //redirect
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            //message
            $this->setSubjectAlert('success', (object) ['code' => 'save_success']);
        } catch(\PDOException $exception) {
            $error = $this->model->handleException($exception);
            $this->setSubjectAlert('danger', $error);
            $redirectRoute = $this->buildRouteToActionFromRoot(sprintf('update-form/%s', $fieldsData->primaryKeyValue));
        }
        //redirect
        $this->redirect($redirectRoute);
    }
    
    /**
     * Performs action after record save
     * to be overridden by children classes if necessary
     * @param mixed $primaryKeyValue
     * @param object $fieldsData as returnd by getSaveFormData method
     */
    protected function doAfterRecordSave($primaryKeyValue, $fieldsData)
    {
    }
    
    /**
     * Deletes record
     */
    protected function delete()
    {
        $primaryKeyField = $this->model->getConfig()->primaryKey;
        $primayKeyFilter = $this->CRUDLconfig->fields[$primaryKeyField]->inputFilter;
        $primaryKeyValue = filter_input(INPUT_POST, $primaryKeyField, $primayKeyFilter);
        try {
            //delete record
            $this->model->delete($primaryKeyValue);
            $redirectRoute = $this->buildRouteToActionFromRoot('list');
            $this->setSubjectAlert('success', (object) ['code' => 'delete_success']);
        } catch(\PDOException $exception) {
            $error = $this->model->handleException($exception);
            $this->setSubjectAlert('danger', $error);
            $redirectRoute = $this->buildRouteToActionFromRoot(sprintf('delete-form/%s', $primaryKeyValue));
        }
        //redirect
        $this->redirect($redirectRoute);
    }
    
    /**********
     * UPLOAD *
     *********/
    
    /**
     * Uploads a file
     */
    protected function upload()
    {
        //xx($_FILES, true);
        //get upload name cleaning file input name from the -upload suffix
        $inputName = array_keys($_FILES)[0];
        $uploadKey = str_replace('-upload', '', $inputName);
        $fileName = $_FILES[$inputName]['name'];
        //return object
        $return = new \stdClass;
        $errors = [];
        //check uploads folder
        $uploadsFolder = $this->model->getUploadsFolder();
        if(!is_dir($uploadsFolder)) {
            //create upload store folder
            mkdir($uploadsFolder, 0755, true);
        }
        //check uploads configuration
        $modelConfig = $this->model->getConfig();
        if(!isset($modelConfig->uploads)) {
            $errors[] = sprintf('Model %s have no uploads configuration', $this->subject);
        //check upload configuration
        }elseif(!isset($modelConfig->uploads[$uploadKey]) || empty($modelConfig->uploads[$uploadKey])) {
            $errors[] = sprintf('Upload %s is not set into model %s configuration', $uploadKey, $this->subject);
        } else {
            //check upload folder
            $uploadFolder = $this->model->getUploadFolder($uploadKey);
            if(!is_dir($uploadFolder)) {
                //create upload store folder
                mkdir($uploadFolder, 0755, true);
            }
            //move uploaded file to upload folder so that each output can access it (because move_uploaded_file deletes file)
            $uploadFilePath = sprintf('%s/%s', $uploadFolder, $fileName);
            move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadFilePath);
            //loop outputs
            foreach ($modelConfig->uploads[$uploadKey] as $outputKey => $output) {
                //check output folder
                $outputFolder = $this->model->getOutputFolder($uploadKey, $outputKey);
                if(!is_dir($outputFolder)) {
                    //create output store folder
                    mkdir($outputFolder, 0755, true);
                    //create default .gitignore file
                    $fp = fopen(sprintf('%s/.gitignore', $outputFolder), 'w');
                    fwrite($fp, sprintf('# Ignore everything in this directory%1$s*%1$s!.gitignore', PHP_EOL));
                    fclose($fp);
                }
                //copy original uploaded file
                $outputFilePath = $this->model->getOutputFilePath($uploadKey, $outputKey, $fileName);
                copy($uploadFilePath, $outputFilePath);
                //handler
                if(isset($output->handler)) {
                    $parameters = array_merge([$outputFilePath], $output->parameters ?? []);
                    call_user_func_array($output->handler, $parameters);
                }
            }
            //delete original uploaded file
            unlink($uploadFilePath);
        }
        //store error
        $return->error = implode('<br>', $errors);
        echo json_encode($return);
    }
    
    /**
     * Resizes an image
     */
    protected static function resizeImage($path, $width, $height)
    {
        Image::load($path)
           ->width($width)
           ->height($width)
           ->save();
    }    
};
