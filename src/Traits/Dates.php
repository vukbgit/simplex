<?php
declare(strict_types=1);

namespace Simplex\Traits;

trait Dates {
    
    /**
     * formats a date from locale format (as specified into language definition) to English format YYYY-MM-DD
     * useful to format user input for saving to database
     * @param string $dateLocale
     */
    protected function formatDateLocaleToEn($dateLocale)
    {
        return \DateTime::createFromFormat($this->language->dateFormat->PHP, $dateLocale)->format('Y-m-d');
    }
}
