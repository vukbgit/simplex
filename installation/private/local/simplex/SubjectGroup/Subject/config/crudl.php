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
            'inputFilter' => [
              'filter' => FILTER_SANITIZE_STRING,
              'flags' => FILTER_FLAG_NO_ENCODE_QUOTES //do not encode single ' and double " quotes
            ],
            'flags' => FILTER_NULL_ON_FAILURE
        ],
        'VARCHAR-FIELD-WITH-REGEXP' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => true,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => [
              'filter' => FILTER_VALIDATE_REGEXP,
              'options' => ['regexp'=>sprintf('/%s/', REGEXP-CONSTANT)],
              'flags' => FILTER_NULL_ON_FAILURE //for nullable fields set to null otherwise woll be saved as 0
            ]
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
                'options' => ['regexp'=>'/^[0-9]{4}-[0-9]{2}-[0-9]{4}$/']
                //italian format
                'options' => ['regexp'=>'/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/']
            ],
        ],
    ],
    /*to build a label when subject is ancestor each element can be:
    * - a field name to extract value from ancestor record
    * - a string to be displayed as-is
    * - an array where:
    *   - first element is a subject name to be found into current page ancestors to use token
    *   - second element is a field name or a string
    */
    /*'labelTokens' => [
        'FIELD-NAME',
        'STRING-TOKEN'
    ],*/
    /*//fields to be marked during cloning
    'clone' => [
        'FIELD-NAME',
    ],*/
    /*//whether to forget table filter
    'forgetFilter' => false,*/
    /*//autocomplete configuration
    'autocomplete' => (object) [
      'orderBy' => [
        ['FIELD-TO-ORDER-BY']
      ],
      'labelFields' => [
        'FIELD-NAME',
        'STRING'
      ]
    ],*/
];
