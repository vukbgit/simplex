<?php
declare(strict_types=1);
//use Nette\Utils\Finder;
use function outputMessage as om;
//bootstrap
include __DIR__ . '/bootstrap.php';
//skip in production
if(ENVIRONMENT !== 'development') {
  exit(formatMessage(
    'h',
    'simplex version change refactoring is applied only in development environment'
  ));
}
//phase (first script argument)
$phases = ['pre', 'post', 'test'];
if(!isset($argv[1]) || !in_array($argv[1], $phases)) {
  exit(formatMessage(
    'e',
    sprintf('script %s must be called passing "phase" as first argument with possible values %s', $argv[0], implode(' or ', $phases))
  ));
} else {
  $phase = $argv[1];
}
om('h', sprintf('Refactoring phase %s', strtoupper($phase)));
switch ($phase) {
  case 'test':
    //second argument is version of refactor file to be tested
    if(!isset($argv[2]) || !is_file(sprintf('%s/%s.php', Simplex\Refactor::REFACTOR_DIR, $argv[2]))) {
      exit(formatMessage(
        'e',
        sprintf('when script %s first argument "phase" is "test" second argument must be a version with a corresponding file into refactors folder "%s"', $argv[0], Simplex\Refactor::REFACTOR_DIR)
      ));
    } else {
      $testFile = sprintf('%s/%s.php', Simplex\Refactor::REFACTOR_DIR, $argv[2]);
    }
  break;
  case 'pre':
    //second argument is minimum version of refactor file to be executed
    $currentVersion = $argv[2] ?? \Composer\InstalledVersions::getVersion('vukbgit/simplex');
  break;
  case 'post':
    $currentVersion = \Composer\InstalledVersions::getVersion('vukbgit/simplex');
  break;
}
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
    if(is_file($currentVersionFilePath)) {
      $previousVersion = trim(file_get_contents($currentVersionFilePath));
    } else {
      $previousVersion = '2.0';
    }
    //check if previous version is lower than current
    if(version_compare($previousVersion, $currentVersion, '<')) {
      om('h', sprintf('simplex version upgrade from %s to %s', $previousVersion, $currentVersion));
      //get refactor files
      $refactor = $DIContainer->get('refactor');
      $refactor->setDryRun(false);
      $files = $refactor->getRefactorFiles($previousVersion, $currentVersion);
      foreach((array) $files as $path => $fileInfo) {
        $fileVersion = $fileInfo->getBasename('.php');
        om('d', sprintf('executing refactor script for version %s', $fileVersion));
        ob_start();
        include $fileInfo->getRealPath();
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
    if(is_file($currentVersionFilePath)) {
      unlink($currentVersionFilePath);
    }
  break;
  case 'test':
    om('h', sprintf('testing refactor file %s', $testFile));
    $refactor = $DIContainer->get('refactor');
    $refactor->setDryRun(true);
    include $testFile;
  break;
}