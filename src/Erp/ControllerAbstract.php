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
    * ERP config object
    **/
    private $config;
    
    /**
    * @param object
    * current user options, set by the UI and stored into area cookie under subject property
    **/
    private $userOptions;
    
    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        //ControllerAbstract jobs
        parent::doBeforeActionExecution($request);
        //store subject
        $this->storeSubject();
        //store model
        $this->storeModel();
        //load ERP config
        $this->loadErpConfig();
        //get cookie stored user options
        $this->getUserOptions();
        //load navigation
        $this->loadAreaNavigation();
        $this->loadSubjectNavigation();
        //set specific CRUDL template parameters
        $this->setTemplateParameter('userData', $this->getAuthenticatedUserData());
        $this->setTemplateParameter('subject', $this->subject);
        $this->setTemplateParameter('currentNavigationVoice', $this->currentNavigationVoice);
        $this->setTemplateParameter('sideBarClosed', $this->getAreaCookie('sideBarClosed') ?? false);
        $this->setTemplateParameter('pathToSubjectTemplate', sprintf('@local/%s/%s/subject.twig', str_replace('\\', '/', getInstanceNamespace($this, true)), TEMPLATES_DEFAULT_FOLDER));
    }

    /**
     * Stores subject
     */
    protected function storeSubject()
    {
        $reflection = new \ReflectionClass($this);
        $nameSpace = explode('\\', $reflection->getNamespaceName());
        $this->subject = array_pop($nameSpace);
        
    }

    /**
     * Stores model passed by action
     */
    protected function storeModel()
    {
        $modelNameSpace = sprintf('%s\Model', getInstanceNamespace($this));
        $this->model = $this->DIContainer->get($modelNameSpace);
    }
    
    /**
     * Loads ERP config which is *always* needed for ERP 
     */
    protected function loadErpConfig()
    {
        //config file must be into class-folder/config/model.php
        $configPath = sprintf('%s/config/erp.php', getInstancePath($this));
        //check path
        if(!is_file($configPath)) {
            throw new \Exception(sprintf('configuration ERP file \'%s\' for subject %s is not a valid path', $configPath, $this->subject));
        }
        //load navigation
        $this->config = require $configPath;
    }
    
    /*********
    * OPTONS *
    *********/
    
    /**
     * Gets user options stored into cookie
     */
    protected function getUserOptions(): object
    {
        $this->userOptions = $this->getAreaCookie()->{$this->subject} ?? new \stdClass;
        return $this->userOptions;
    }

    /**
     * Sets an user option to be stored into cookie
     * @param string $userOption
     * @param mixed $value
     */
    protected function setUserOptions(string $userOption, $value)
    {
        $this->userOptions->$userOption = $value;
        $this->setAreaCookie($this->subject, $this->userOptions);
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

    /************************
    * DEFAULT CRUDL ACTIONS *
    ************************/

    /**
     * Lists records
     */
    protected function list()
    {
        //check list query modifiers
        $this->setListQueryModifiers();
        //get model list
        $records = $this->model->get(
            $this->buildListWhere(),
            $this->userOptions->sorting ?? []
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
        $this->setUserOptions('sorting', $sorting);
    }
    
    /**
    * Replaces current filter
    * @param string $filter
    */
    public function replaceListFilter(string $filter)
    {
        $this->setUserOptions('filter', $filter);
    }
    
    /**
    * Builds list query where based on modifiers
    * @return array as accepted by Pixie query builder
    */
    private function buildListWhere(): array
    {
        $where = [];
        //filter
        $userOptions = $this->getUserOptions();
        if(isset($userOptions->filter)) {
            //loop config fields
            foreach ((array) $this->config->fields as $fieldName => $fieldConfig) {
                if(!isset($fieldConfig->tableFilter) || $fieldConfig->tableFilter) {
                    $where[] = [$fieldName, 'LIKE', sprintf('%%%s%%', $userOptions->filter)];
                }
            }
        }
        return $where;
    }
}
