<?php
/**
* Variables to be included into area subjects configuration files
**/
//current area in slug form
$area = 'backend';
//authentication object to be used with routes that need authentication verification
$authentication = (object) [
    'action' => 'verify',
    'urls' => (object) [
        'signInForm' => sprintf('/%s/sign-in-form', $area),
        'signOut' => sprintf('/%s/sign-out', $area),
    ]
];
