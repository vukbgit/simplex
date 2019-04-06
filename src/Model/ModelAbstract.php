<?php
declare(strict_types=1);

namespace Simplex\Model;

/*
* class that rapresents a model, an atomic structure of data stored in a database
*/
abstract class ModelAbstract
{
    /**
    * @var ServerRequestInterface
    */
    protected $request;

    /**
    * @var ResponseInterface
    */
    protected $response;

    /**
    * @var ContainerInterface
    * DI container, to create/get instances of classes needed at runtime (such as models)
    */
    protected $DIContainer;

    /**
    * @var Environment
    * Twig environment
    */
    protected $twig;

    /**
    * @var object
    * values extracted from route parsing
    */
    protected $routeParameters;

    /**
    * @var string
    * action passed by route
    */
    protected $action;

    /**
    * Constructor
    * @param ContainerInterface $DIContainer
    * @param ResponseInterface $response
    * @param Environment $twigEnvironment
    */
    public function __construct(ContainerInterface $DIContainer, ResponseInterface $response, Environment $twigEnvironment)
    {
        $this->DIContainer = $DIContainer;
        $this->response = $response;
        $this->twig = $twigEnvironment;
    }
}
