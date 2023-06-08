<?php
/*
* database configuration (as defined for usmanhalalit/pixie, see https://github.com/usmanhalalit/pixie)
* inidexed by ENVIRONMENT constant possible values
*/
return [
  'development' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => DB_DEVELOPMENT_USER,
    'password' => DB_DEVELOPMENT_PASSWORD,
    'database' => DB_DEVELOPMENT_DATABASE,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    /*'options'   => array( // PDO constructor options, optional
    )*/
  ],
  'production' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => DB_PRODUCTION_USER,
    'password' => DB_PRODUCTION_PASSWORD,
    'database' => DB_PRODUCTION_DATABASE,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    /*'options'   => array( // PDO constructor options, optional
    )*/
  ],
];
