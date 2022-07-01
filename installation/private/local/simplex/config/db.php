<?php
/*
* database configuration (as defined for usmanhalalit/pixie, see https://github.com/usmanhalalit/pixie)
* inidexed by ENVIRONMENT constant possible values
*/
return [
    'development' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'username' => 'USERNAME',
        'password' => 'PASSWORD',
        'database' => 'DATABASE',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        /*'options'   => array( // PDO constructor options, optional
        )*/
    ]
];
