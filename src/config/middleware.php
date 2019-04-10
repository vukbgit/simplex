<?php
return [
    //routes parser
    $DIContainer->get('simplexFastRoute'),
    //authentication
    [
        function($request) {
            $requestParameters = $request->getAttributes()['parameters'];
            return isset($requestParameters->authentication);
        },
        $DIContainer->get('simplexAuraAuth'),
    ],
    //handler of the request
    $DIContainer->get('requestHandler')
];
