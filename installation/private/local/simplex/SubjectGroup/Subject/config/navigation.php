<?php
//import subject variables
require 'variables.php';
//import model configuration
$modelConfig = require 'model.php';
return [
    //action to be linked ont the top of every subject page
    'globalActions' => [
        'list' => (object) [
            'route' => sprintf('/backend/%s/list', $subject),
            'permissions' => [sprintf('manage-%s', $subject)],
        ],
        'insert-form' => (object) [
            'route' => sprintf('/backend/%s/insert-form', $subject),
            'permissions' => [sprintf('manage-%s', $subject)],
        ]
    ],
    //for record actions placeholder enclosed by curly brackets {} will be substituded by field values found into record
    'recordVisibleActions' => [
        'update-form' => (object) [
            'route' => sprintf('/backend/%s/update-form/{%s}', $subject, implode('/', $modelConfig->primaryKey)),
            'permissions' => [sprintf('manage-%s', $subject)],
        ],
        'delete-form' => (object) [
            'route' => sprintf('/backend/%s/delete-form/{%s}', $subject, implode('/', $modelConfig->primaryKey)),
            'permissions' => [sprintf('manage-%s', $subject)],
        ]
    ]
];
