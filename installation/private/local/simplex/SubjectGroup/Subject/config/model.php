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
                //instance method, use special first element 'this' 
                'handler' => ['this','NON-STATIC-METHOD-NAME'],
                //static method
                'handler' => [sprintf('\%s\Controller', $subjectNamespace),'STATIC-METHOD-NAME'],
                'parameters' => [PARAMETER1,...]
            ]
        ]
    ],
    //in case there is a TABLE-NAME_locales table
    'locales' => [
        'LOCALE-FIELD-1',
        'LOCALE-FIELD-2',
    ],
    //force using a locales view, to be used in case in case there is NOT a TABLE-NAME_locales table but some foreign keys tables are localized and therefore there is a localized view
    //'useLocalizedView' => true,
    //whether model has a poistion field
    'position' => (object) [
        'field' => false,
        //fields to narrow by next position look up
        'contextFields' => []
    ],
    //map from table fields to fullcalendar event object properties (https://fullcalendar.io/docs/event-object)
    'calendarFieldsMap' => (object) [
      'FULLCALENDAR-EVENT-PROPERTY' => 'DB-FIELD',
      'FULLCALENDAR-EVENT-PROPERTY' => ['DB-FIELD-1', 'ANY-STRING', 'DB-FIELD-2'],
    ]
];
