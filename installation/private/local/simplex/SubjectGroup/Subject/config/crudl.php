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
              'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
              'flags' => FILTER_FLAG_NO_ENCODE_QUOTES //do not encode single ' and double " quotes
            ],
            'flags' => FILTER_NULL_ON_FAILURE
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
              'options' => [
                //, AS THOUSAND SEPARATOR AND . AS DECIMAL SEPARATOR
                'regexp'=>'/^(?:[0-9]{0,3},?)?[0-9]{1,3}(?:\.[0-9]{1,2})?$/',
                //. AS THOUSAND SEPARATOR AND , AS DECIMAL SEPARATOR
                'regexp'=>'/^(?:[0-9]{0,3}\.?)?[0-9]{1,3}(?:,[0-9]{1,2})?$/',
              ],
              'flags' => FILTER_NULL_ON_FAILURE //for nullable fields set to null otherwise woll be saved as 0
            ]
        ],
        'imponibile_importo' => (object) [
            'table' => (object)[
                //boolean defaults to true
                'filter' => false,
                //boolean defaults to false
                'total' => true
            ],
            //input filter see https://www.php.net/manual/en/filter.filters.php
            'inputFilter' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                //'options' => array('regexp'=>'/[0-9]{0,3}\.{0,1}[0-9]{1,3},{0,1}[0-9]{0,2}/')
                'options' => array('regexp'=>sprintf('/%s/', FLOAT_REGEX))
            ],
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
                //dates range italian
                'options' => ['regexp'=>'/^[0-9]{2}-[0-9]{2}-[0-9]{4} \/ [0-9]{2}-[0-9]{2}-[0-9]{4}$/']
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
    ],
    //default list order
    'listOrderBy' => [
      ['data_domanda', 'DESC'],
    ],
    */
];
