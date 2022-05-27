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
use function Simplex\slugToPSR1Name;
//import area variables
require 'variables.php';
//definitions array
return [
    /*****************
    * AUTHENTICATION *
    *****************/
    //sign in form
    [
        'method' => 'GET',
        'route' => $signInFormRoute,
        'handler' => [
            'backendAuthenticationController',
            [
                'action' => 'sign-in-form',
                'area' => $area,
                'signInUrl' => $signInRoute
            ]
        ]
    ],
    //sign in action
    [
        'method' => 'POST',
        'route' => $signInRoute,
        'handler' => [
            'backendAuthenticationController',
            [
                'area' => $area,
                'action' => 'sign-in',
                'authentication' => (object) [
                    'action' => 'sign-in',
                    'signInMethods' => [
                        'htpasswd' => (object) [
                            'path' => sprintf('private/local/simplex/%s/config/.htpasswd', slugToPSR1Name($area, 'class'))
                        ],/*
                        'db' => (object) [
                            'path' => 'private/local/simplex/config/db.php',
                            'algo' => BACKEND_PASSWORD_ALGO,
                            'table' => 'v_utenti',
                            //first username, second crypted password, third user role
                            'fields' => ['email', 'password', 'gruppo_utenti'],
                            'condition' => 'active = 1'
                        ]*/
                    ],
                    'usersRolesPath' => sprintf('private/local/simplex/%s/config/users-roles.php', slugToPSR1Name($area, 'class')),
                    'permissionsRolesPath' => sprintf('private/local/simplex/%s/config/permissions-roles.php', slugToPSR1Name($area, 'class')),
                    'urls' => (object) [
                        'signInForm' => $signInFormRoute,
                        'successDefault' => $successfulSignInRoute,
                    ]
                ]
            ]
        ]
    ],
    //sign out action
    [
        'method' => 'GET',
        'route' => $signOutRoute,
        'handler' => [
            'backendAuthenticationController',
            [
                'area' => $area,
                'action' => 'sign-out',
                'authentication' => (object) [
                    'action' => 'sign-out',
                    'urls' => (object) [
                        'signInForm' => $signInFormRoute
                    ]
                ]
            ]
        ]
    ]
];
