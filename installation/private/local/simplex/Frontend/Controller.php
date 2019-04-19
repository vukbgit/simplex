<?php
declare(strict_types=1);

namespace Simplex\Local\Frontend;
use Simplex\Controller\ControllerAbstract;

class Controller extends ControllerAbstract
{
    use Traits\Frontend;

    /**
    * Displays home page
    **/
    protected function home() {
        $this->setPageTitle('Home');
        $this->renderTemplate();
    }
}
