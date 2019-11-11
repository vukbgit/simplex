<?php
return (object) [
    'table' => 'TABLE-NAME',
    'view' => 'VIEW-NAME',
    'primaryKey' => 'PRIMARY-KEY-FIELD',
    'uploads' => [
        'UPLOAD-KEY' => [
            'OUTPUT-WITHOUT-HANDLER-KEY' => (object) [],
            'OUTPUT-WITH-HANDLER-KEY' => (object) [
                'handler' => ['\Simplex\Local\PATH\TO\CONTROLLER\CLASS','STATIC-METHOD-NAME'],
                'parameters' => [PARAMETER1,...]
            ]
        ]
    ],
    'locales' => [
        'LOCALE-FIELD-1',
        'LOCALE-FIELD-2',
    ]
];
