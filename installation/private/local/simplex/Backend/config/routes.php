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
use Simplex\Authentication;
use Simplex\Local\Partners;
use function Simplex\slugToPSR1Name;
//current area in slug form
$area = 'backend';
//definitions array
return [
    /*****************
    * AUTHENTICATION *
    *****************/
    //sign in form
    [
        'method' => 'GET',
        'route' => sprintf('/%s/{action:sign-in-form}[/]', $area),
        'handler' => [
            'backendAuthenticationController',
            [
                'action' => 'sign-in-form',
                'area' => $area,
                'signInUrl' => sprintf('/%s/sign-in', $area)
            ]
        ]
    ],
    //sign in action
    [
        'method' => 'POST',
        'route' => sprintf('/%s/{action:sign-in}', $area),
        'handler' => [
            'backendAuthenticationController',
            [
                'area' => $area,
                'authentication' => (object) [
                    'action' => 'sign-in',
                    'signInMethods' => [
                        'htpasswd' => (object) [
                            'path' => sprintf('private/local/simplex/%s/config/.htpasswd', slugToPSR1Name($area, 'class'))
                        ]
                    ],
                    'usersRolesPath' => sprintf('private/local/simplex/%s/config/users-roles.php', slugToPSR1Name($area, 'class')),
                    'permissionsRolesPath' => sprintf('private/local/simplex/%s/config/permissions-roles.php', slugToPSR1Name($area, 'class')),
                    'urls' => (object) [
                        'signInForm' => sprintf('/%s/sign-in-form', $area),
                        'successDefault' => sprintf('/%s/categorie-partners/list', $area),
                    ]
                ]
            ]
        ]
    ],
    //sign out action
    [
        'method' => 'GET',
        'route' => sprintf('/%s/{action:sign-out}', $area),
        'handler' => [
            'backendAuthenticationController',
            [
                'area' => $area,
                'authentication' => (object) [
                    'action' => 'sign-out',
                    'urls' => (object) [
                        'signInForm' => sprintf('/%s/sign-in-form', $area)
                    ]
                ]
            ]
        ]
    ]
];
