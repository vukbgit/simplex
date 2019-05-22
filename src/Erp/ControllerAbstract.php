<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\ControllerWithTemplateAbstract;
use function Simplex\slugToPSR1Name;

/*
* Extends the ControllerAbstract class adding CRUDL (https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) funcionalities:
* - handling of list, create-form, update-form, delete-form, create, update and delete actions
* - requires route to pass a 'model' parameter, a class with the same name will be looked for in current namespace
*/
abstract class ControllerAbstract extends ControllerWithTemplateAbstract
{
    /**
    * @var array
    * navigation
    */
    protected $navigation;
    
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
        //ControllerAbstract jobs
        parent::doBeforeActionExecution($request);
        //load navigation
        $this->loadNavigation();
        //store model
        $this->storeModel();
        //set specific CRUDL template parameters
        $this->setTemplateParameter('userData', $this->getAuthenticatedUserData());
        $this->setTemplateParameter('sideBarClosed', $this->cookie->get('sideBarClosed'));
    }

    /**
     * Loads area navigation
     */
    protected function loadNavigation()
    {
        //check path
        if(!defined('AREA_NAVIGATION_PATH') || !is_file(AREA_NAVIGATION_PATH)) {
            throw new \Exception('constant AREA_NAVIGATION_PATH *MUST* be defined for current area and must be a valid path');
        }
        $loadedNavigation = require AREA_NAVIGATION_PATH;
        xx($loadedNavigation);
        $this->loadNavigationLevel($loadedNavigation);
    }
    
    /**
     * Loads a navigation level
     * @param array $navigationLevel
     */
    protected function loadNavigationLevel(array &$loadedNavigationLevel)
    {
        foreach ($loadedNavigationLevel as $voiceKey => $voiceProperties) {
            //check permission
            if(isset($voiceProperties->permissions) && !$this->checkAtLeastOnePermission($voiceProperties->permissions)) {
                unset($loadedNavigationLevel->$voiceKey);
                continue;
            }
        }
    }
    
    /**
     * Stores model passed by action
     */
    protected function storeModel()
    {
        $modelSlug = $this->routeParameters->model ?? null;
        //model name is set ino route
        if($modelSlug) {
            //get model
            $modelName = slugToPSR1Name($modelSlug, 'class');
            $this->model = $this->DIContainer->get($modelName);
        //model name is NOT set ino route
        } else {
            throw new \Exception('current route *MUST* pass a "model" parameter');
        }
    }

    /**
     * Gets model namespace
     */
    protected function getModelPath()
    {
        $reflection = new \ReflectionClass($this->model);
        $namespace = $reflection->getName();
        return str_replace(['Simplex\Local\\', '\Model'], '', $namespace);
    }

    /************************
    * DEFAULT CRUDL ACTIONS *
    ************************/

    /**
     * Lists records
     */
    protected function list()
    {
        //get model list
        $records = $this->model->get();
        $this->setTemplateParameter('records', $records);
        //render
        $this->renderTemplate(sprintf(
            '@local/%s/%s/list.%s',
            $this->getModelPath(),
            TEMPLATES_DEFAULT_FOLDER,
            TEMPLATES_EXTENSION
        ));
    }
}
