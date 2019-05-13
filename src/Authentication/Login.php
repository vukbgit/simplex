<?php
declare(strict_types=1);

namespace Simplex\Authentication;
use Simplex\Controller\ControllerWithTemplateAbstract;

/*use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\Controller\ControllerAbstract;*/

class Login extends ControllerWithTemplateAbstract
{
    /**
     * Lists records
     */
    protected function loginForm()
    {
        //set page title
        $this->setPagetitle(_('Login'));
        //render
        $this->renderTemplate('@share/templates/crudl/login-form.twig');
    }
}
