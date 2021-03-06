<?php
return (object) [
    //whether model use _locales table
    'localized' => false,
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
        ],
        'DATE-FIELD' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => true,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                //anglo-saxon date format
                'options' => array('regexp'=>'/^[0-9]{4}-[0-9]{2}-[0-9]{4}$/')
                //italian format
                'options' => array('regexp'=>'/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/')
            ],
        ],
    ],
    /*//to build a label when subject is ancestor, fields names or raw strings
    'labelTokens' => [
        'FIELD-NAME',
        'STRING-TOKEN'
    ]*/
    /*//fields to be marked during cloning
    'clone' => [
        'FIELD-NAME',
    ]*/
];
