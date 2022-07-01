<?php
/**
* Variables to be included into area subjects configuration files
**/
//current area in slug form
$area = 'backend';
//sign in form route
$signInFormRoute = sprintf('/%s/sign-in-form', $area);
//sign in action route
$signInRoute = sprintf('/%s/sign-in', $area);
//sign out action route
$signOutRoute = sprintf('/%s/sign-out', $area);
//default route to redirect after a successful login
$successfulSignInRoute = sprintf('/%s/DEFAULT-SUBJECT/list', $area);
//authentication object to be used with routes that need authentication verification
$authentication = (object) [
    'action' => 'verify',
    'urls' => (object) [
        'signInForm' => $signInFormRoute,
        'signOut' => $signOutRoute,
    ]
];
