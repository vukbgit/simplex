<?php
/*******
* DUMP *
*******/
Kint\Renderer\RichRenderer::$folder = false;
Kint\Renderer\RichRenderer::$theme = 'aante-light.css';
Kint::$aliases[] = 'x';
Kint::$aliases[] = 'xx';

/**
* Dumps a variable
* @param mixed $var
* @param bool $expand whether to expand objects by default
**/
function x($var, $expand = false, $force = false) {
    if(ENVIRONMENT == 'development' || $force == true) {
        if($expand) {
            Kint::$expanded = true;
        }
        !Kint::dump($var);
    }
}
/**
* Dumps a variable and exits
* @param mixed $var
* @param bool $expand whether to expand objects by default
**/
function xx($var, $expand = false) {
    x($var, $expand);
    exit;
}
