<?php
declare(strict_types=1);

namespace Simplex\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use function Simplex\slugToPSR1Name;

/*
* Extends the ControllerAbstract class adding CRUDL (https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) funcionalities:
* - handling of list, create-form, update-form, delete-form, create, update and delete actions
* - requires route to pass a 'model' parameter, a class with the same name will be looked for in current namespace
*/
abstract class ControllerWithCRUDLAbstract extends ControllerAbstract
{
    /**
    * @var mixed
    * model passed by route
    */
    protected $model;

    /**
    * @var array
    * default hierarchy for shared enterprise templates (patternfly templates stored into templates/enterprise), it can be overridden using the setEnterpriseTemplatesParent method
    */
    protected $enterpriseTemplatesParents = [
        'action' => 'enterprise/authenticated.twig',
        'authenticated' => 'enterprise/area.twig',    //this value raise a twig error and must be set by concrete class
        'area' => 'application.twig'    //this value raise a twig error and must be set by concrete class
    ];

    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        //ControllerAbstract jobs
        parent::doBeforeActionExecution($request);
        //store model
        $this->storeModel();
        //set specific CRUDL template parameters
        $this->setTemplateParameter('enterpriseTemplatesParents', $this->enterpriseTemplatesParents);
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
     * Sets a parent template for one of the shared enterpise templates
     * @param string $templateLevel: level whose parent is being set, possible values (so far):
     *  - ac(tion): templates for predefined actions (list, save-form, delete-form)
     *  - au(thenticated): template for the post-authenticated user
     *  - ar(ea): template for the whole area
     */
    protected function setEnterpriseTemplatesParent(string $templateLevel, string $parentTemplatePath)
    {
        $templateLevels = [
            'ac' => 'action',
            'au' => 'authenticated',
            'ar' => 'area'
        ];
        $templateLevel = $templateLevels[$templateLevel] ?? $templateLevel;
        $this->enterpriseTemplatesParents[$templateLevel] = $parentTemplatePath;
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
        $records = $this->model->getList();
        $this->stp('records', $records);
        //render
        $this->renderTemplate();
    }
}
