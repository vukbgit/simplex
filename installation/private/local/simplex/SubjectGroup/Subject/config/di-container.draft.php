<?php
/*
* definition for classes to be used by the DI Container, see http://php-di.org/doc/container.html
* included by SHARE_DIR . /vukbgit/simplex/src/config/di-container.php
*/
declare(strict_types=1);
//PHP-DI functions
use function DI\create;
use function DI\get;
//import area variables
require sprintf('%s/Backend/config/variables.php', PRIVATE_LOCAL_DIR);
//import subject variables
require 'variables.php';
//definitions array
return [
    sprintf('%s-controller', $subject) => create(sprintf('%s\Controller', $subjectNamespace))
        ->constructor(get('DIContainer'), get('response'), get('templateEngine'), get('cookieManager')),
    sprintf('%s-model', $subject) => create(sprintf('%s\Model', $subjectNamespace))
        ->constructor(get('queryBuilder')),
];
