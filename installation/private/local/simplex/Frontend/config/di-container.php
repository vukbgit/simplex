<?php
/*
* definition for classes to be used by the DI Container, see http://php-di.org/doc/container.html
* included by SHARE_DIR . /vukbgit/simplex/src/config/di-container.php
*/
declare(strict_types=1);
//PHP-DI functions
use function DI\create;
use function DI\get;
//LOCAL CLASSES
use Simplex\Local\Frontend;
//definitions array
return [
  'frontend-controller' => create(Frontend\Controller::class)
    ->constructor(
      get('DIContainer'),
      get('response'),
      get('templateEngine'),
      get('cookieManager')
    )
];
