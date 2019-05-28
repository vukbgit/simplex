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
**/
function x($var) {
    if(ENVIRONMENT == 'development') {
        !Kint::dump($var);
    }
}
/**
* Dumps a variable and exits
* @param mixed $var
**/
function xx($var) {
    //if(ENVIRONMENT == 'development') {
        !Kint::dump($var);
        exit;
    //}
}
