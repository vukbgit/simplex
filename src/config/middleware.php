<?php
return [
    //routes parser
    $DIContainer->get('simplexFastRouteMiddleware'),
    //authentication
    [
        function($request) {
            $requestParameters = $request->getAttributes()['parameters'];
            return isset($requestParameters->authentication);
        },
        $DIContainer->get('simplexAuthenticationMiddleware'),
    ],
    //handler of the request
    $DIContainer->get('requestHandler'),
];
