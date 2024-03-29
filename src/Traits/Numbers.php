<?php
declare(strict_types=1);

namespace Simplex\Traits;

trait Numbers {
    
    /**
     * formats a float number from locale format to English format with dot as decimal point and no thousand separators and
     * useful to format user input for saving to database
     * @param float $float
     */
    private function formatFloatFromLocaleToEn($float)
    {
        $localeconv = localeconv();
        return str_replace([$localeconv['thousands_sep'], $localeconv['decimal_point']], ['', '.'], (string) $float);
    }
}
