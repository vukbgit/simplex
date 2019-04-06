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
     * Get invoked by request handler
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //store request
        $this->storeRequest($request);
        $modelSlug = $this->routeParameters->model ?? null;
        //model name is set ino route
        if($modelSlug) {
            //get model
            $modelName = slugToPSR1Name($modelSlug, 'c');
            $this->model = $this->DIContainer->get($modelName);
        //model name is NOT set ino route
        } else {
            throw new \Exception('current route *MUST* pass a \'model\' parameter');
        }
        //handle action
        $this->handleActionExecution();
        //return response
        return $this->response;
    }

    /**
     * Processes action associated to the route
     */
    protected function getModel()
    {
        //get model list
        //render
        $this->renderTemplate();
    }

    /**
     * Processes action associated to the route
     */
    protected function list()
    {
        //get model list
        ~r($this->model);
        //render
        $this->renderTemplate();
    }
}
