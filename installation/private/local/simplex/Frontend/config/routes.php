<?php
/*
* for routes definitions syntax details: https://github.com/nikic/FastRoute
* each route is an array with elements:
*       method: uppercase HTTP method(s), as a string (ie: 'GET', 'POST') ora an array (ie: ['GET', 'POST'])
*       route: route pattern, tips:
*           parameter: {parameter-name}
*           parameter value: {parameter-name:parameter-value}
*           optional pattern end: [/optional-ending-pattern]
*       handler: an array with two elements:
*           (mandatory) callable (class to be invoked through magic __invoke method) in the form ClassName::class
*           an optional associative array of parameters
*
* Both the parameters defined into route pattern and the optional parameters added to handler are stored into the ServerRequestInterface 'parameters' attribute instance which is passed to the handler class __invoke() method as first parameter and can be get by $invoke-first-parameter->getAttributes()->parameters
* Parameters defined into route pattern overload handler parameters with the same name
*/
//handlers namespaces
use Simplex\Local\Frontend;
//current area in slug form
$area = 'frontend';
//definitions array
return [
    [
        'method' => 'GET',
        'route' => '[/]',
        'handler' => [
            Frontend\Controller::class,
            [
                'area' => $area,
                'action' => 'home'
            ]
        ]
    ]
];
