<?php
declare(strict_types = 1);

namespace Simplex\Local\Frontend\Traits;

/**
 * Frontend commons
 */
trait Frontend
{
    /**
    * sets page title
    * @param string $pageTitle
    */
    protected function setPageTitle(string $pageTitle)
    {
        parent::setPageTitle(sprintf('%s :: %s', APPLICATION, $pageTitle));
    }
}
