<?php
/**************
* ENVIRONMENT *
**************/
//development | production or any other value set into root .htaccess file
if(getenv('REDIRECT_ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('REDIRECT_ENVIRONMENT'));
} else {
    echo 'No evironment defined, you must set up root .htaccess';
    exit;
}
define('SHARE_DIR', 'private/share/vukbgit/simplex/src');
define('LOCAL_DIR', 'private/local/simplex');
define('SHARE_CONFIG_DIR', sprintf('%s/config', SHARE_DIR));
define('LOCAL_CONFIG_DIR', sprintf('%s/config', LOCAL_DIR));
/*****************
* CACHE & ERRORS *
*****************/
//temporary files folder (i.e. for caching)
define('TMP_DIR', '../tmp');
//folder with HTTP errors pages
define('ERROR_DIR', sprintf('%s/errors', SHARE_DIR));
/********
* DEBUG *
********/
define('TECH_EMAIL', null);
/************
* TEMPLATES *
************/
//folder where Twig template engine starts looking for templates files
define('SHARE_TEMPLATES_DIR', 'private/share/vukbgit/simplex/src/templates');
define('LOCAL_TEMPLATES_DIR', LOCAL_DIR);
//twig template files extension
define('TEMPLATES_EXTENSION', 'twig');
/********
* LOCAL *
********/
define('APPLICATION', null);
