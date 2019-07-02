<?php

namespace Simplex;

use \Nette\Utils\Finder;

if (!function_exists('Simplex\slugToPSR1Name')) {
    /**
    * Turns a word in slug notation (route part) into a form as defined by PSR1 standard (https://www.php-fig.org/psr/psr-1/) for class names, method names and such
    * @param string $slug: term in slug form to be translated into PSR1
    * @param string $type: the type of element to translate to, so far c(lass) | m(ethod)
    *
    * @return string
    */
    function slugToPSR1Name(string $slug, string $type) : string
    {
        switch ($type) {
            case 'class':
            case 'c':
                return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
            break;
            case 'method':
            case 'm':
                return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $slug))));
            break;
            default:
                throw new \Exception(sprintf('function Simplex\slugToPSR1Name: type parameter \'%s\' value is not handled', $type));
            break;
        }
    }
}

if (!function_exists('Simplex\PSR1NameToSlug')) {
    /**
    * Turns a word in PSR1 standard (https://www.php-fig.org/psr/psr-1/) for class names, method names and such to slug notation
    * @param string $psr1: term in PSR1 form to be translated into slug
    *
    * @return string
    */
    function PSR1NameToSlug(string $psr1) : string
    {
        $pattern = '/(?<!^)([A-Z]{1,1})/';
        return strtolower(preg_replace_callback(
            $pattern,
            function($matches) {
                return sprintf('-%s', strtolower($matches[0]));
            },
            $psr1
        ));
    }
}

if (!function_exists('Simplex\requireFromFiles')) {
    /**
    * Searches a folder for files by file name pattern and requires them
    * @param string $folder: to search recursively into
    * @param string $pattern: file name to search for, * can be used
    */
    function requireFromFiles(string $folder, string $pattern)
    {
        foreach (Finder::findFiles($pattern)->from($folder) as $file) {
            $filePath = $file->__toString();
            require $filePath;
        }
    }
}

if (!function_exists('Simplex\mergeArrayFromFiles')) {
    /**
    * Searches a folder for files by file name pattern, expectes an array returned by file inclusion, merges all returned arrays
    * @param string $folder: to search recursively into
    * @param string $pattern: file name to search for, * can be used
    *
    * @return array
    */
    function mergeArrayFromFiles(string $folder, string $pattern) : array
    {
        $return = [];
        foreach (Finder::findFiles($pattern)->from($folder) as $file) {
            $filePath = $file->__toString();
            $fileArray = require $filePath;
            if(is_array($fileArray)) {
                $return = array_merge($return, $fileArray);
            } else {
                throw new \Exception(sprintf('file "%s" MUST return an array on requirement', $filePath));
            }
        }
        return $return;
    }
}

if (!function_exists('Simplex\mergeObjects')) {
    /**
    * Merges two objects sio that properties from the second one override correspndent of the first one
    * @param object $object1
    * @param object $object2
    *
    * @return object
    */
    function mergeObjects(object $object1, object $object2) : object
    {
        return (object) array_merge((array) $object1, (array) $object2);
    }
}

if (!function_exists('Simplex\getInstanceNamespace')) {
    /**
    * Gets namespace of a class defined into Simplex\Local
    * @param mixed $instance
    * @param bool $fromWithinSimplex: whteher first part of namespace is to be truncated
    *
    * @return object
    */
    function getInstanceNamespace($instance, bool $fromWithinSimplex = false) : string
    {
        $reflection = new \ReflectionClass($instance);
        $namespace = $reflection->getName();
        $search = [sprintf('\%s', $reflection->getShortName())];
        if($fromWithinSimplex) {
            $search[] = 'Simplex\Local\\';
        }
        return str_replace($search, '', $namespace);
    }
}

if (!function_exists('Simplex\getInstancePath')) {
    /**
    * Gets namespace of a class defined into Simplex\Local
    * @param mixed $instance
    * @param bool $fromWithinSimplex: whteher first part of namespace is to be truncated
    *
    * @return object
    */
    function getInstancePath($instance) : string
    {
        $namespace = getInstanceNamespace($instance, true);
        return sprintf('private/local/simplex/%s', str_replace('\\', '/', $namespace));
    }
}

if (!function_exists('Simplex\loadLanguages')) {
    /**
    * Loads configured languages from json file
    *
    * @return object
    */
    function loadLanguages() : object
    {
        $languagesConfigFilePath = sprintf('%s/languages.json', LOCAL_CONFIG_DIR);
        return json_decode(file_get_contents($languagesConfigFilePath));
    }
}
