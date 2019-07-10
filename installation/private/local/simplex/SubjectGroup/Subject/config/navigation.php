<?php
//import subject variables
require 'variables.php';
//import model configuration
$modelConfig = require 'model.php';
return [
    'globalActions' => [
        'list' => (object) [
            'routeFromSubject' => 'list',
            'permissions' => [sprintf('manage-%s', $subject)],
        ],
        'insert-form' => (object) [
            'routeFromSubject' => 'insert-form',
            'permissions' => [sprintf('manage-%s', $subject)],
        ]
    ],
    //for record actions placeholder enclosed by curly brackets {} will be substituded by field values found into record
    'recordVisibleActions' => [
        'update-form' => (object) [
            'routeFromSubject' => sprintf('update-form/{%s}', $modelConfig->primaryKey),
            'permissions' => [sprintf('manage-%s', $subject)],
        ],
        'delete-form' => (object) [
            'routeFromSubject' => sprintf('delete-form/{%s}', $modelConfig->primaryKey),
            'permissions' => [sprintf('manage-%s', $subject)],
        ]
    ]
];
