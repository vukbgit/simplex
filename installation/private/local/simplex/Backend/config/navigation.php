<?php
/*
'subject-key' => (object) [
    'route' => '/route/to/subject/action' | false (i.e. for a voice with submenu),
    'permissions' => ['permission-key'], //to restrict voice visualization, see also permissions-roles file
    'icon' => 'icon-partners', //icon class
    'navigation' => [...]   //submenu
    
],
*/
return [
  'area' => [
    'SUBJECT-KEY' => (object) [
      //route can be relative or an URL
      'route' => '/ROUTE',
      //target => '_blank'
      'permissions' => ['PERMISSION-KEY'],
      'icon' => 'ICON-CLASS',
      'navigation' => [
          
      ]
    ]
  ]
];
