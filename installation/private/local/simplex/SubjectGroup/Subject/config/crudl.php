<?php
return (object) [
    //whether model use _locales table
    'localized' => true,
    'fields' => [
        'PRIMARY-KEY-FIELD' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => false,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => FILTER_VALIDATE_INT,
        ],
        'VARCHAR-FIELD' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => true,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES],
        ]
    ],
    /*//to build a label when subject is ancestor, fields names or raw strings
    'labelTokens' => [
        'FIELD-NAME',
        'STRING-TOKEN'
    ]*/
];
