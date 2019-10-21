<?php
/********
* LOCAL *
********/
//included into exceptions message
define('TECH_EMAIL', '');
//used to set page title tag in some templates
define('BRAND', '');
/**************
* ENVIRONMENT *
**************/
define('HOST', $_SERVER['HTTP_HOST'] ?? null);
define('ABS_PATH_TO_ROOT', str_replace('/private/local/simplex/config', '', __DIR__));
//development | production or any other value set into root .htaccess file
if(getenv('REDIRECT_ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('REDIRECT_ENVIRONMENT'));
} else {
    echo 'No evironment defined, you must set up root .htaccess';
    exit;
}
define('PRIVATE_SHARE_DIR', sprintf('%s/private/share/vukbgit/simplex/src', ABS_PATH_TO_ROOT));
define('PRIVATE_LOCAL_DIR', sprintf('%s/private/local/simplex', ABS_PATH_TO_ROOT));
define('SHARE_CONFIG_DIR', sprintf('%s/config', PRIVATE_SHARE_DIR));
define('LOCAL_CONFIG_DIR', sprintf('%s/config', PRIVATE_LOCAL_DIR));
define('MIDDLEWARE_QUEUE_PATH', sprintf('%s/middleware.php', SHARE_CONFIG_DIR));
define('PUBLIC_SHARE_DIR', 'public/share');
/*****************
* CACHE & ERRORS *
*****************/
//temporary files folder (i.e. for caching)
define('TMP_DIR', sprintf('%s/../tmp', ABS_PATH_TO_ROOT));
//folder with HTTP errors pages
define('ERROR_DIR', sprintf('%s/errors', PRIVATE_SHARE_DIR));
/**********
* COOKIES *
**********/
//cookies default duration  in minutes (525600 minutes = 1 year)
define('COOKIE_DURATION', 525600 * 5);
/************
* TEMPLATES *
************/
//folder where Twig template engine starts looking for templates files
//namespaced into twig as @share
define('SHARE_TEMPLATES_DIR', sprintf('%s/private/share/vukbgit/simplex/src', ABS_PATH_TO_ROOT));
//namespaced into twig as @local
define('LOCAL_TEMPLATES_DIR', PRIVATE_LOCAL_DIR);
//twig template files extension
define('TEMPLATES_EXTENSION', 'twig');
//twig folder to search for default action template, path from the controller folder
define('TEMPLATES_DEFAULT_FOLDER', 'templates');
/***************
* TRANSLATIONS *
***************/
//folder for translations po and mo files
define('TRANSLATIONS_DIR', sprintf('%s/locales', PRIVATE_LOCAL_DIR));
/**********
* CAPTCHA *
**********/
//it supposed to be only one captcha configuration for the whole site (do we ever need more?!)
//path to captcha config file
define('CAPTCHA_CONFIG_PATH', sprintf('%s/private/local/simplex/config/captcha.php', ABS_PATH_TO_ROOT));
