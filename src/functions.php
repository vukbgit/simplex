<?php

namespace Simplex;

if (!function_exists('Simplex\slugToPSR1Name')) {
    /**
    * Turns a word in slug notation (route part) into a form as defined by PSR1 standard (https://www.php-fig.org/psr/psr-1/) for class names, method names and such
    * @param string $slug: term in slug form to be translated
    * @param string $type: the type of element to translate to, so far c(lass) | m(ethod)
    *
    * @return string
    */
    function slugToPSR1Name(string $slug, string $type) : string
    {
        switch ($type) {
            case 'class':
            case 'c':
                // code...
            break;
            case 'method':
            case 'm':
                // code...
            break;
            default:
                throw new \Exception(sprintf('Type \'%s\' is not handled', $type));
            break;
        }
    }
}
