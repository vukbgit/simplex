<?php
declare(strict_types=1);

namespace Simplex\Traits;

trait Numbers {
    private function formatFloatFromLocaleToEn($float)
    {
        $localeconv = localeconv();
        return str_replace([$localeconv['thousands_sep'], $localeconv['decimal_point']], ['', '.'], $float);
    }
}
