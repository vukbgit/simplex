<?php
//import subject variables
require 'variables.php';
return (object) [
    'table' => 'TABLE-NAME',
    'view' => 'VIEW-NAME',
    'primaryKey' => 'PRIMARY-KEY-FIELD',
    //primary key alias is useful in contexts where table primary key proper name cannot be used
    //i.e: all of schema table have primary key field named 'id', when used as foreign key in db views or into routes definitions these fields *will* be aliased
    //'primaryKeyAlias' => 'PRIMARY-KEY-FIELD-ALIAS',
    'uploads' => [
        'UPLOAD-KEY' => [
            'OUTPUT-WITHOUT-HANDLER-KEY' => (object) [],
            'OUTPUT-WITH-HANDLER-KEY' => (object) [
                //method must be static
                'handler' => [sprintf('\%s\Controller', $subjectNamespace),'STATIC-METHOD-NAME'],
                'parameters' => [PARAMETER1,...]
            ]
        ]
    ],
    'locales' => [
        'LOCALE-FIELD-1',
        'LOCALE-FIELD-2',
    ],
    //whether model has a poistion field
    'position' => (object) [
        'field' => false,
        //fields to narrow by next position look up
        'contextFields' => []
    ]
];
