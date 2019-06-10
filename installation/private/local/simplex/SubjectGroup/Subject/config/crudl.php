<?php
return (object) [
    'fields' => [
        'PRIMARY-KEY-FIELD' => (object) [
            //boolean defaults to true
            'tableFilter' => false,
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => FILTER_VALIDATE_INT,
        ],
        'VARCHAR-FIELD' => (object) [
            //boolean defaults to true
            'tableFilter' => true,
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES],
        ]
    ]
];
