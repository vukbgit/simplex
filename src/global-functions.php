<?php
/**
* Dumps a variable
* @param mixed $var
**/
function x($var) {
    if(ENVIRONMENT == 'development') {
        !d($var);
    }
}
/**
* Dumps a variable and exits
* @param mixed $var
**/
function xx($var) {
    if(ENVIRONMENT == 'development') {
        !d($var);
        exit;
    }
}
