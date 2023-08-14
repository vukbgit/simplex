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
            //int type
            'inputFilter' => FILTER_VALIDATE_INT,
            //uuid type
            'inputFilter' => [
              'filter' => FILTER_VALIDATE_REGEXP,
              'options' => [
                'regexp'=>sprintf('/%s/', UUID_REGEXP),
              ]
            ]
        ],
        'VARCHAR-FIELD' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => true,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            //for safe environments like backends, to save rich text
            'inputFilter' => FILTER_UNSAFE_RAW
            //for unsafe environments but content must be decoded before using in HTML
            /*'inputFilter' => [
              'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
              'flags' => FILTER_FLAG_NO_ENCODE_QUOTES //do not encode single ' and double " quotes
            ],*/
        ],
        'DECIMAL-FIELD' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => true,
                //boolean defaults to false
                'total' => false
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => [
              'filter' => FILTER_VALIDATE_REGEXP,
              'options' => ['regexp'=>sprintf('/%s/', FLOAT_REGEX)],
              'flags' => FILTER_NULL_ON_FAILURE //for nullable fields set to null otherwise will be saved as 0
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
                //'options' => ['regexp'=>'/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/']
            ],
        ],
        //position field is automatically handled
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
    ],
    //default list order
    'listOrderBy' => [
      ['data_domanda', 'DESC'],
    ],
    */
    /*//list query limit
    'queryLimit' => 100,
];
