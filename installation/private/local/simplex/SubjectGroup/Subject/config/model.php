<?php
return (object) [
    'table' => 'categorie_partners',
    'view' => 'v_categorie_partners',
    'primaryKey' => [
        'id_categoria_partners'
    ],
    'uploads' => [
        'UPLOAD-KEY' => [
            'OUTPUT-WITHOUT-HANDLER-KEY' => (object) [],
            'OUTPUT-WITH-HANDLER-KEY' => (object) [
                'handler' => ['\Simplex\Local\PATH\TO\CONTROLLER\CLASS','STATIC-METHOD-NAME'],
                'parameters' => [PARAMETER1,...]
            ]
        ]
    ]
];
