<?php
declare(strict_types=1);

namespace Simplex\Local\Backend;

use Simplex\Erp\ControllerWithoutCRUDLAbstract;

class Controller extends ControllerWithoutCRUDLAbstract
{
    /**
    * Displays dashboard
    **/
    protected function dashboard() {
        $this->renderTemplate();
    }
}
