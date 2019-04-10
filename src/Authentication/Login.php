<?php
declare(strict_types=1);

namespace Simplex\Authentication;
use Simplex\Controller\ControllerAbstract;

/*use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\Controller\ControllerAbstract;*/

class Login extends ControllerAbstract
{
    /**
     * Lists records
     */
    protected function loginForm()
    {
        //render
        $this->renderTemplate('crudl/login-form.twig');
    }
}
