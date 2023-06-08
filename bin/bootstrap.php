<?php
declare(strict_types=1);
/******************
* ERROR REPORTING *
******************/
ini_set("display_errors", "1");
error_reporting(E_ALL);
/*********************
* NAMESPACES ALIASES *
*********************/
use DI\ContainerBuilder;
use function Simplex\requireFromFiles;
/*****************
* SAPI DETECTION *
*****************/
/**
 * Type of SAPI (https://en.wikipedia.org/wiki/Server_application_programming_interface)
 * @var string SAPI_TYPE: cli|web
 * @link https://github.com/arcanisgk/WEB-CLI-Detector
 */
define('SAPI_TYPE',
  defined('STDIN')
  || php_sapi_name() === "cli"
  || (stristr(PHP_SAPI, 'cgi') && getenv('TERM'))
  || (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) ? 'cli' : 'web'
);
/*************************
* IMPORT INI CONFIG FILE *
*************************/
switch (SAPI_TYPE) {
  //cli sapi
  case 'cli':
    #try to grab path to ini config file from environmet variable
    if(!$pathToIniConfig = getenv('PATH_TO_INI_CONFIG')) {
      #try to grab it from -i option
      if(!$pathToIniConfig = getopt("i:")['i'] ?? false) {
        exit(formatMessage('error', 'path to ini config file must be passed as PATH_TO_INI_CONFIG environment variable or as -i option, exit'));
      }
    }
  break;
  //web sapi
  case 'web':
    if(!$pathToIniConfig) {
      exit(formatMessage('error', 'path to ini config file must be set into <var>$pathToIniConfig</var> before including <b>bootstrap.php</b>, exit'));
    }
  break;
}
//sanitize path
$pathToIniConfig = filter_var($pathToIniConfig, FILTER_SANITIZE_URL);
//check path
if(!is_file($pathToIniConfig)) {
  exit(formatMessage('error', sprintf('invalid path "<i>%s</i>" to ini config file, exit', $pathToIniConfig)));
} else {
  //extract ini values as constants
  foreach(parse_ini_file($pathToIniConfig) as $name => $value) {
    define($name, $value);
  }
}
//check mandatory settings
$missingMandatories = [];
foreach(['ENVIRONMENT','ABS_PATH_TO_ROOT', 'TMP_DIR', 'PHP_CLI'] as $settingName) {
  if(!defined($settingName)) {
    $missingMandatories[] = $settingName;
  }
}
if(!empty($missingMandatories)) {
  exit(formatMessage('error', sprintf('the following mandatory configuration settings must be set into %s: %s, exit', $pathToIniConfig, implode(', ', $missingMandatories))));
}
/***********
* COMPOSER *
***********/
require_once sprintf('%s/private/share/packagist/autoload.php', ABS_PATH_TO_ROOT);
/*****************
* ERROR HANDLING *
*****************/
$whoops = new \Whoops\Run(null);
//exception handler
switch(ENVIRONMENT) {
    case 'development':
    switch (SAPI_TYPE) {
      case 'cli':
        $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler);
      break;
      case 'web':
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
      break;
    }
    break;
    case 'production':
        $whoops->pushHandler(new \Simplex\ErrorHandler);
    break;
}
$whoops->register();
/********
* PATHS *
********/
define('PRIVATE_SHARE_BASE_DIR', sprintf('%s/private/share', ABS_PATH_TO_ROOT));
define('PRIVATE_SHARE_PACKAGIST_DIR', sprintf('%s/packagist', PRIVATE_SHARE_BASE_DIR));
define('PRIVATE_SHARE_SIMPLEX_DIR', sprintf('%s/vukbgit/simplex', PRIVATE_SHARE_PACKAGIST_DIR));
define('PRIVATE_SHARE_DIR', sprintf('%s/src', PRIVATE_SHARE_SIMPLEX_DIR));
define('PRIVATE_LOCAL_DIR', sprintf('%s/private/local/simplex', ABS_PATH_TO_ROOT));
define('SHARE_CONFIG_DIR', sprintf('%s/config', PRIVATE_SHARE_DIR));
define('LOCAL_CONFIG_DIR', sprintf('%s/config', PRIVATE_LOCAL_DIR));
define('PUBLIC_SHARE_DIR', 'public/share');
define('PUBLIC_LOCAL_DIR', 'public/local');
define('PUBLIC_LOCAL_SIMPLEX_DIR', sprintf('%s/simplex', PUBLIC_LOCAL_DIR));
//include from local namespace constants definition files
//it can be disabled to avoid collision during refactoring scripts
if(!isset($includeLocalContants) || !$includeLocalContants) {
  requireFromFiles(sprintf('%s/private/local/simplex', ABS_PATH_TO_ROOT), 'constants.php');
}
/************
* CONTAINER
* definitions into private/local/simplex/config/di-container.php
************/
$DIContainerBuilder = new ContainerBuilder();
//cache
switch(ENVIRONMENT) {
    case 'production':
        $DIContainerBuilder->enableCompilation(TMP_DIR);
        $DIContainerBuilder->writeProxiesToFile(true, TMP_DIR . '/proxies');
    break;
}
$DIContainerBuilder->useAutowiring(false);
$DIContainerBuilder->addDefinitions(require sprintf('%s/di-container.php', SHARE_CONFIG_DIR));
$DIContainer = $DIContainerBuilder->build();
/*******************
* BOOTSTRAP OUTPUT *
*******************/
/**
 * Formats a message for both cli and web SAPI_TYPE
 * @param string $messageType: d(efault) | h(ighlight) | s(uccess) | e(rror)
 * @param string $message
 * @return string message formatted for current SAPI_TYPE
 */
function formatMessage(string $messageType, string $message): string
{
$colors = [
  'cli' => [
    'LIGHT_GRAY' => "\033[0;37m",
    'GREEN' => "\033[0;32m",
    'RED' => "\033[0;31m",
    'ORANGE' => "\033[0;33m",
  ],
  'web' => [
    'LIGHT_GRAY' =>'#838383',
    'GREEN' => '#0f0',
    'RED' => '#f00',
    'ORANGE' => '#c4a000',
  ],
];
$messageTypesColors = [
  'default' => 'LIGHT_GRAY',
  'success' => 'GREEN',
  'error' => 'RED',
  'highlight' => 'ORANGE',
];

//normalize message type
switch ($messageType) {
  case 'd':
    $messageType = 'default';
  break;
  case 'h':
    $messageType = 'highlight';
  break;
  case 's':
    $messageType = 'success';
  break;
  case 'e':
    $messageType = 'error';
  break;
}

//get color appropriate for message type
$color = $colors[SAPI_TYPE][$messageTypesColors[$messageType]];
switch (SAPI_TYPE) {
  case 'cli':
    $defaultColor = $colors[SAPI_TYPE][$messageTypesColors['default']];
    //remove tags and apply color
    return sprintf(
      "%s%s%s\n",
      $color,
      filter_var($message, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES),
      $defaultColor
    );
  break;
  case 'web':
    return sprintf(
      '<div style="font-family:monospace;color:%s"><big>%s</big></div>',
      $color,
      $message
    );
  break;
}

}
/**
 * Outputs a message for both cli and web SAPI_TYPE
 * @param string $messageType: d(efault) | h(ighlight) | s(uccess) | e(rror)
 * @param string $message
 * @return string message formatted for current SAPI_TYPE
 */
function outputMessage(string $messageType, string $message): void
{
  echo formatMessage($messageType, $message);
}
/**
 * handy aliases
 */
function outputDefault(string $message) {
  outputMessage('d', $message);
}
function outputSuccess(string $message) {
  outputMessage('s', $message);
}
function outputError(string $message) {
  outputMessage('e', $message);
}
function outputHighlight(string $message) {
  outputMessage('h', $message);
}
