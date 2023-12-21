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
//current area in slug form
$area = 'frontend';
//definitions array
return [
    [
        'method' => 'GET',
        'route' => '[/]',
        'handler' => [
          'frontend-controller',
            [
                'area' => $area,
                'action' => 'home'
            ]
        ]
    ]
];

/********************
 * LOCALIZED ROUTES *
 * *****************/
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
*       locale: object, allows routes to be translation aware and to build current route link into language menu, properties:
*           key: string, unique route key, it *MUST* correspond to a key into template labels category 'navigation'
*           tokens: array, route / separated parts, each element can be:
*             '__lang', string fixed placeholder for route language code
*             a string, a pure token "fixed-token" ora a route parameter "{a-route-parameter}"
*             an object, properties:
*               source: the translation source gettext|db
*               key: the translation key, it is used as name of the route token parameters (../{key:slugged-translation}/..), meaning is according to source:
*                 if source = gettext: unique route key, it *MUST* correspond to a key into template labels category 'navigation'
*                 if source = db -> TODO
*               values: array, in case the route parameters correspondig to token can have multiple values, an array with navigation template label category keys that correspond to such values
*
* Both the parameters defined into route pattern and the optional parameters added to handler are stored into the ServerRequestInterface 'parameters' attribute instance which is passed to the handler class __invoke() method as first parameter and can be get by $invoke-first-parameter->getAttributes()->parameters
* Parameters defined into route pattern overload handler parameters with the same name
*/
//handlers namespaces
use Simplex\Local\Frontend;
use function Simplex\loadLanguages;
use function Simplex\buildLocaleRoutes;

global $DIContainer;
$query = $DIContainer->get('queryBuilder');
$targetsModel = $DIContainer->get('targets-model');
//current area in slug form
$area = 'frontend';
$languages = loadLanguages('local');
$languagesList = implode('|', array_keys(get_object_vars($languages)));
//definitions array
$routesDefinitions = [
  //MAIN MENU
  //home
  [
      'method' => 'GET',
      //'route' => sprintf('/{lang:%s}', $languagesList),
      'handler' => [
          Frontend\Controller::class,
          [
            'area' => $area,
            'action' => 'home',
            'locale' => (object) [
              'key' => 'menu-home',
              'tokens' => [
                '__lang',
              ],
            ],
          ]
      ]
  ],
  //route with static translations
  [
      'method' => 'GET',
      'handler' => [
          Frontend\Controller::class,
          [
              'area' => $area,
              'action' => 'ACTION-SLUG',
              'locale' => (object) [
                'key' => 'ACTION-LABEL-KEY',  //key of the translation as used into navigation.php and defined in some template
                'tokens' => [
                  '__lang',
                  (object) [
                    //'optional' => true,
                    'source' => 'gettext',   //gettext | db
                    'key' => 'ACTION-LABEL-KEY',
                  ]
                ],
              ],
          ]
      ]
  ],
  //route with token translated into db
  [
      'method' => 'GET',
      'handler' => [
          'CLASS-controller',
          [
              'area' => $area,
              'subject' => 'SUBJET-SLUG',
              'action' => 'ACTION-SLUG',
              'locale' => (object) [
                'key' => 'ROUTE-KEY',
                'tokens' => [
                  '__lang',
                  (object) [
                    //'optional' => true,
                    'source' => 'gettext',   //gettext | db
                    'key' => 'ACTION-LABEL-KEY',  //something like the subject key translated in some template
                  ],
                  sprintf('{slug-categoria:%s}', SLUG_REGEXP)
                ],
              ],
          ]
      ]
  ],
];
$routes = buildLocaleRoutes($languages, $routesDefinitions);

return $routes;
