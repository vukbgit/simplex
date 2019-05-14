<?php
declare(strict_types=1);

namespace Simplex\ERP;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Simplex\Controller\ControllerWithTemplateAbstract;

/*use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\Controller\ControllerAbstract;*/

class Login extends ControllerWithTemplateAbstract
{
    /*
    * path to login form template
    */
    protected $templatePath;
    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    * @param Environment $twigEnvironment
    * @param string $templatePath
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response, Environment $templateEngine, string $templatePath)
    {
        parent::__construct($DIContainer, $response, $templateEngine);
        $this->templatePath = $templatePath;
    }
    
    /**
     * Lists records
     */
    protected function loginForm()
    {
        //set page title
        $this->setPagetitle(_('Login'));
        //render
        $this->renderTemplate($this->templatePath);
    }
}
