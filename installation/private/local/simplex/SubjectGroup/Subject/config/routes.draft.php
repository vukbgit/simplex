<?php
/*
* for routes definitions syntax details: https://github.com/nikic/FastRoute
* each route is an array with elements:
*       method: uppercase HTTP method(s), as a string (ie: 'GET', 'POST') ora an array (ie: ['GET', 'POST'])
*       route: route pattern, tips:
*           parameter: {parameter-name}
*           parameter value: {parameter-name:parameter-value}
*           optional pattern end: [/optional-ending-pattern]
*           ancestors: to add to the route informations about subject ancestor, for each ancestor add:
*               a route parameter with name 'ancestor-X' (where X should an integer index starting form 0 but can be any string) and value the ancestor subject key
*               a route parameter named after the ancestor subject primay key
*       handler: an array with two elements:
*           (mandatory) callable (class to be invoked through magic __invoke method) in the form ClassName::class
*           an optional associative array of parameters
*
* Both the parameters defined into route pattern and the optional parameters added to handler are stored into the ServerRequestInterface 'parameters' attribute instance which is passed to the handler class __invoke() method as first parameter and can be get by $invoke-first-parameter->getAttributes()->parameters
* Parameters defined into route pattern overload handler parameters with the same name
*/
//import area variables
require sprintf('%s/Backend/config/variables.php', PRIVATE_LOCAL_DIR);
//import subject variables
require 'variables.php';
//import model configuration
$modelConfig = require 'model.php';
//definitions array
return [
  //standard ERP actions, corresponding methods are defined in Simplex\Erp\ControllerAbstract
  [
    'method' => ['GET','POST'],
    'route' => sprintf('/{area:%s}/{subject:%s}/{action:autocomplete|set-side-bar-state}', $area, $subject),
    'handler' => [
      sprintf('%s-controller', $subject),
      [
        'authentication' => $authentication
      ]
    ]
  ],
  //CRUD actions
  [
    'method' => ['GET','POST'],
    //int primary key
    'route' => sprintf('/{area:%s}/{subject:%s}/{action}[/{%s:\d+}]', $area, $subject, $modelConfig->primaryKey),
    //UUID primary key
    'route' => sprintf('/{area:%s}/{subject:%s}/{action}[/{%s:%s}]', $area, $subject, $modelConfig->primaryKey, UUID_REGEXP_CORE),
    //children route with int primary key
    //'route' => sprintf('/{area:%s}/{ancestor0:ANCESTOR-SUBJECT}/{ANCESTOR-PRIMARY-KEY:\d+}/{subject:%s}/{action}[/{%s:\d+}]', $area, $subject, $modelConfig->primaryKey),
    //children route with UUID primary key
    //'route' => sprintf('/{area:%s}/{ancestor0:ANCESTOR-SUBJECT}/{ANCESTOR-PRIMARY-KEY:%4$s}/{subject:%s}/{action}[/{%s:%4$s}]', $area, $subject, $modelConfig->primaryKey, UUID_REGEXP_CORE),
    'handler' => [
      sprintf('%s-controller', $subject),
      [
        'authentication' => $authentication
      ]
    ]
  ],
];
