<?php
declare(strict_types=1);

namespace Simplex\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\slugToPSR1Name;

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
        //do all the jobs performed by ControllerAbstract on invocation
        $response = parent::__invoke($request, $handler);
        //get model name
        $modelSlug = $this->routeParameters->model ?? null;
        //model name is set ino route
        if($modelSlug) {
            //get model
            $modelName = slugToPSR1Name($modelSlug, 'x');
            /*$this->model = $this->container->get();
            //check method existence
            if(method_exists($this, $methodName)) {
                //call method
                call_user_func([$this, $methodName]);
            } else {
                throw new \Exception(sprintf('current route is associated to action \'%s\' but method \'%s\' of class %s does not exist', $this->action, $methodName, static::class));
            }*/
        //model name is NOT set ino route
        } else {
            throw new \Exception('current route *MUST* pass a \'model\' parameter');
        }
        return $response;
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
        //render
        $this->renderTemplate();
    }
}
