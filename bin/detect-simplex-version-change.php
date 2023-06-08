<?php
declare(strict_types=1);
use Nette\Utils\Finder;
use function outputMessage as om;
//bootstrap
require_once __DIR__ . '/bootstrap.php';
/*******************
* SCRIPT ARGUMENTS *
*******************/
//skip in production
if(ENVIRONMENT !== 'development') {
  exit(formatMessage(
    'h',
    'simplex version change refactoring is applied only in development environment'
  ));
}
//phase
$phases = ['pre', 'post'];
if(!isset($argv[1]) || !in_array($argv[1], $phases)) {
    exit(formatMessage(
      'e',
      sprintf('script %s must be called passing "phase" as first argument with possible values %s', $argv[0], implode(' or ', $phases))
    ));
} else {
    $phase = $argv[1];
}
//get current simplex version
$currentVersion = json_decode(file_get_contents(__DIR__ . '/../package.json'))->version;
//version file path
$currentVersionFilePath = TMP_DIR . '/simplex-version';
//manage phase
switch ($phase) {
  case 'pre':
    om('h', sprintf('before update simplex is at version %s (saved into %s)', $currentVersion, $currentVersionFilePath));
    //save version
    file_put_contents($currentVersionFilePath, $currentVersion);
  break;
  case 'post':
    //get pre update version
    $previousVersion = trim(file_get_contents($currentVersionFilePath));
    //compare versions
    //check if previous version is lower than current
    if(version_compare($previousVersion, $currentVersion, '<')) {
      om('h', sprintf('simplex version upgrade from %s to %s', $previousVersion, $currentVersion));
      //get refactor files
      $refactor = $DIContainer->get('refactor');
      $files = $refactor->getRefactorFiles($previousVersion, $currentVersion);
      foreach((array) $files as $path => $fileInfo) {
        $fileVersion = $fileInfo->getBasename('.php');
        om('d', sprintf('executing refactor script for version %s', $fileVersion));
        ob_start();
        //execute script
        passthru(PHP_CLI . ' ' . $path);
        $log = ob_get_contents();
        ob_flush();
        ob_end_clean();
        //save log file
        $pathToLogFolder = sprintf(
          '%s/log',
          PRIVATE_LOCAL_DIR
        );
        $pathToLogFile = sprintf(
          '%s/refactor-%s.log',
          $pathToLogFolder,
          $fileVersion
        );
        if(!is_dir($pathToLogFolder)) {
          mkdir($pathToLogFolder);
        }
        //clean log 
        $log = preg_replace('/\033\[0;[0-9]{2}m/', '', $log);
        file_put_contents($pathToLogFile, $log);
        om('s', sprintf('log for version %s saved into %s', $fileVersion, $pathToLogFile));
      }
    }
    //remove version file
    unlink($currentVersionFilePath);
  break;
}
