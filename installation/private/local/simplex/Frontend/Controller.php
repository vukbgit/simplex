<?php
declare(strict_types=1);

namespace Simplex\Local\Frontend;

use Simplex\Controller\ControllerWithTemplateAbstract;

class Controller extends ControllerWithTemplateAbstract
{
    use Traits\Frontend;

    /**
    * Displays home page
    **/
    protected function home() {
        $this->renderTemplate();
    }
}
