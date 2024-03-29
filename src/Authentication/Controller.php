<?php
declare(strict_types=1);

namespace Simplex\Authentication;

use Simplex\Controller\ControllerWithTemplateAbstract;
use function Simplex\slugToPSR1Name;

/*use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\Controller\ControllerAbstract;*/

class Controller extends ControllerWithTemplateAbstract
{
    /**
     * Displays sign in form
     */
    protected function signInForm()
    {
        //render
        $templatePath = sprintf('@local/%s/%s/sign-in-form.twig', slugToPSR1Name($this->area, 'class'), TEMPLATES_DEFAULT_FOLDER);
        $this->renderTemplate($templatePath);
        //delete sign in  cookie
        $this->cookie->setAreaCookie($this->area, 'authenticationReturnCode', null);
    }
    
    /**
     * Void method associatod to sign in route, since login operation is performed by Simplex\Authentication\Middleware but every root MUST have an associated handler
     */
    protected function signIn()
    {
    }
    
    /**
     * Void method associatod to sign out route, since login operation is performed by Simplex\Authentication\Middleware but every root MUST have an associated handler
     */
    protected function signOut()
    {
    }
}
