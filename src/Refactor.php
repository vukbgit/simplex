<?php
declare(strict_types=1);

namespace Simplex;

use Nette\Utils\Finder;

/*
* Class to make changes to code
*/
class Refactor
{
  /**
   * Output messages
   * @var bool $verbose
   */
  private $verbose = true;
  
  /**
   * Do not make changes
   * @var bool $dryRun
   */
  private $dryRun = true;
  
  /**
   * Do not make changes
   * @var bool $dryRun
   */
  const REFACTOR_DIR = PRIVATE_SHARE_SIMPLEX_DIR . '/refactor';
  
  /**
   * Calls refactors, to be inserted as post-update-cmd script into root composer.json
   * @param bool $verbose
   */
  private static function refactoring(string $phase)
  {
    $command = sprintf(
      '%s %s/../bin/refactor.php %s',
      $_SERVER['_'],
      __DIR__,
      $phase
    );
    passthru($command);
  }
    
  /**
   * Calls refactors, to be inserted as post-update-cmd script into root composer.json
   * @param bool $verbose
   */
  public static function preRefactoring()
  {
    self::refactoring('pre');
  }
    /**
   * Calls refactors, to be inserted as post-update-cmd script into root composer.json
   * @param bool $verbose
   */
  public static function postRefactoring()
  {
    self::refactoring('post');
  }
  
  /**
   * Set verbosity
   * @param bool $verbose
   */
  public function setVerbose(bool $verbose)
  {
    $this->verbose = $verbose;
  }
  
  /**
   * Set dry run
   * @param bool $dryRun
   */
  public function setDryRun(bool $dryRun)
  {
    $this->dryRun = $dryRun;
  }
  
  /**
   * Outputs a message (if verbosity allow)
   * @param string $messageType: d(efault) | h(ighlight) | s(uccess) | e(rror)
   * @param string $message
   */
  public function outputMessage(string $messageType, string $message)
  {
    if($this->verbose) {
      outputMessage($messageType, $message);
    }
  }
  
  /**
   * Outputs a job title
   * @param string $title
   */
  public function outputTitle(string $title)
  {
    if($this->verbose) {
      $titleLen = strlen($title);
      outputMessage('h', "\n" . str_repeat('*', $titleLen));
      outputMessage('h', $title);
      outputMessage('h', str_repeat('*', $titleLen));
    }
  }
  
  /**
   * Gets files
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @return array
   *          path-to-file => Nette\Utils\FileInfo
   */
  private function getFiles(
    array $folders,
    array $exclude = [],
    array $filesNames = ['*']
  ): array
  {
    $this->outputMessage(
      'd',
      sprintf(
        'getting from folders [%s] files with mask [%s]',
        implode(', ', $folders),
        implode(', ', $filesNames)
      )
    );
    $search = Finder::findFiles($filesNames);
      //recursive search
    $search->from($folders);
    $search->exclude($exclude);
    $files = $search->collect();
    return $files;
  }
  
  /**
   * Gets refactor files
   * @param string $minVersion: minimum simplex semver to search refactor files for
   * @param string $maxVersion: maximum simplex semver to search refactor files for
   * @return array
   *          path-to-file => Nette\Utils\FileInfo
   */
  public function getRefactorFiles(string $minVersion, string $maxVersion)
  {
    $files = Finder::findFiles('*')
      //->in(PRIVATE_SHARE_SIMPLEX_DIR . '/refactor')
      ->in(SELF::REFACTOR_DIR)
      ->collect();
    foreach((array) $files as $path => $fileInfo) {
      $fileVersion = $fileInfo->getBasename('.php');
      if(version_compare($minVersion, $fileVersion, '>=') || version_compare($fileVersion, $maxVersion, '>')) {
        unset($files[$path]);   
      }
    }
    return $files;
  }
  
  /**
   * Searches for a pattern into files
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @param bool $reverse whether return files that do not contain pattern
   * @return array
   *          path-to-file => Nette\Utils\FileInfo
   */
  public function searchPatternInFiles(
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*'],
    bool $reverse = false
  )
  {
    //get files
    $files = $this->getFiles($folders, $exclude, $filesNames);
    $this->outputMessage(
      'd',
      sprintf(
        'searching for pattern "%s"',
        $pattern
      )
    );
    //loop files
    foreach((array) $files as $i => $fileInfo) {
      $path = $fileInfo->getRealPath();
      $content = file_get_contents($path);
      if(
        (!$reverse && strpos($content, $pattern) === false)
        ||
        ($reverse && strpos($content, $pattern) !== false)
      ) {
        //unset($files[$path]);
        unset($files[$i]);
      }
    }
    return $files;
  }
  
  /**
   * Searches for a pattern into files and performs an operation
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $operation: replace
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @param string $replacement, in case or $operation = replace
   * @return void
   */
  public function searchPatternManipulateFiles(
    string $operation,
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*'],
    string $replacement = ''
  ): void
  {
    //get files
    $files = $this->getFiles($folders, $exclude, $filesNames);
    //search pattern
    $files = $this->searchPatternInFiles($pattern, $folders, $exclude, $filesNames);
    //loop files
    $found = false;
    foreach((array) $files as $fileInfo) {
      $path = $fileInfo->getRealPath();
      //read content
      $content = file_get_contents($path);
      switch ($operation) {
        case 'replace':
          $content = str_replace($pattern, $replacement, $content, $replacementsNumber);
          $found = $replacementsNumber > 0;
        break;
      }
      //if necessary overwrite file
      if($found && !$this->dryRun) {
        file_put_contents(
          $path,
          $content
        );
        $this->outputMessage(
          's',
          sprintf(
            'file %s overwritten with changes',
            $path
          )
        );
      }
    }
  }
  
  /**
   * Searches for a pattern into files lines and performs an operation
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $operation: replace | delete (= delete line)
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @param string $replacement, in case or $operation = replace
   * @return void
   */
  public function searchPatternManipulateLines(
    string $operation,
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*'],
    string $replacement = ''
  ): void
  {
    //get files
    $files = $this->getFiles($folders, $exclude, $filesNames);
    //search pattern
    $files = $this->searchPatternInFiles($pattern, $folders, $exclude, $filesNames);
    //loop files
    foreach((array) $files as $fileInfo) {
      $path = $fileInfo->getRealPath();
      //read into lines
      $lines = file($path);
      //loop lines
      $found = false;
      foreach((array) $lines as $lineIndex => $line) {
        //find pattern in line
        if(strpos($line, $pattern) !== false) {
          $this->outputMessage(
            'h',
            sprintf(
              'found pattern "%s" into file %s at line %s',
              $pattern,
              $path,
              $lineIndex + 1
            )
          );
          $this->outputMessage(
            'd',
            $line
          );
          $found = true;
          switch ($operation) {
            case 'replace':
              $lines[$lineIndex] = str_replace($pattern, $replacement, $line);
            break;
            case 'delete':
              //remove line
              unset($lines[$lineIndex]);
            break;
          }
        }
      }
      //if necessary overwrite file
      if($found && !$this->dryRun) {
        file_put_contents(
          $path,
          implode("", $lines)
        );
        $this->outputMessage(
          's',
          sprintf(
            'file %s overwritten with changes',
            $path
          )
        );
      }
    }
  }
  
  /**
   * Searches for a pattern into files and replaces it
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @param string $replacement
   * @return void
   */
  public function searchPatternReplace(
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*'],
    string $replacement = ''
  ): void
  {
    $this->outputTitle(
      sprintf(
        'search pattern "%s" to replace it',
        $pattern
      )
    );
    $this->searchPatternManipulateFiles(
      'replace',
      $pattern,
      $folders,
      $exclude,
      $filesNames,
      $replacement
    );
  }
  
  /**
   * Searches for a pattern into files lines and replaces it (giving informations about lines where pattern has been found)
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @param string $replacement
   * @return void
   */
  public function searchPatternInLinesReplace(
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*'],
    string $replacement = ''
  ): void
  {
    $this->outputTitle(
      sprintf(
        'search pattern "%s" into lines to replace it',
        $pattern
      )
    );
    $this->searchPatternManipulateLines(
      'replace',
      $pattern,
      $folders,
      $exclude,
      $filesNames,
      $replacement
    );
  }
  
  /**
   * Searches for a pattern into files and deletes line
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @return void
   */
  public function searchPatternDeleteLine(
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*']
  ): void
  {
    $this->outputTitle(
      sprintf(
        'search pattern "%s" to delete lines',
        $pattern
      )
    );
    $this->searchPatternManipulateLines(
      'delete',
      $pattern,
      $folders,
      $exclude,
      $filesNames
    );
  }
  
  /**
   * Searches into files if pattern is missing
   * @see https://doc.nette.org/en/utils/finder for search patterns
   * @param string $pattern
   * @param array $folders to search into
   * @param array $exclude folder and files to be excluded from search
   * @param array $filesNames mask to filter by name files to be searched
   * @return void
   */
  public function searchMissingPattern(
    string $pattern,
    array $folders,
    array $exclude = [],
    array $filesNames = ['*']
  ): void
  {
    $this->outputTitle(
      sprintf(
        'search missing pattern "%s"',
        $pattern
      )
    );
    $files = $this->searchPatternInFiles(
      $pattern,
      $folders,
      $exclude,
      $filesNames,
      true
    );
    if(!empty($files)) {
      $this->outputMessage(
        'e',
        sprintf(
          'ACTION REQUESTED!',
          $pattern
        )
      );
      $this->outputMessage(
        'h',
        sprintf(
          'pattern "%s" not found into these files:',
          $pattern
        )
      );
      //foreach(array_keys($files) as $path) {
      foreach($files as $fileInfo) {
        $path = $fileInfo->getRealPath();
        $this->outputMessage(
          'd',
          $path
        );
      }
    }
  }
  
  /**
   * Copies and eventually replaces a file
   * @param string $sourcePath path to the file to be to be copied into target path
   * @param string $targetPath path where copy $sourcePath to
   * @return void
   */
  public function copyFile(
    string $sourcePath,
    string $targetPath
  ): void
  {
    $this->outputTitle(sprintf(
      "copy file \n%s\nto %s",
      $sourcePath,
      $targetPath
    ));
    //check paths
    $pathsOk = true;
    if(!is_file($sourcePath)) {
      $pathsOk = false;
      $this->outputMessage(
        'e',
        sprintf(
          'path %s is not valid',
          $sourcePath
        )
      );
    }
    if($pathsOk && !$this->dryRun) {
      if(copy($sourcePath, $targetPath)) {
        $this->outputMessage(
          's',
          sprintf(
            'file "%s" copied to "%s"',
            $targetPath,
            $sourcePath
          )
        );
      } else {
        $this->outputMessage(
          'e',
          sprintf(
            'file "%s" could not be replaced by "%s"',
            $targetPath,
            $sourcePath
          )
        );
      }
    }
  }
  
  /**
   * Deletes a file
   * @param string $targetPath path of file to delete
   * @return void
   */
  public function deleteFile(
    string $targetPath
  ): void
  {
    $this->outputTitle(sprintf(
      "delete file \n%s\n",
      $targetPath
    ));
    //check paths
    $pathsOk = true;
    if(!is_file($targetPath)) {
      $pathsOk = false;
      $this->outputMessage(
        'h',
        sprintf(
          'file %s does not exist',
          $targetPath
        )
      );
    }
    if($pathsOk && !$this->dryRun) {
      if(unlink($targetPath)) {
        $this->outputMessage(
          's',
          sprintf(
            'file "%s" deleted',
            $targetPath
          )
        );
      } else {
        $this->outputMessage(
          'e',
          sprintf(
            'file "%s" could not be deleted',
            $targetPath
          )
        );
      }
    }
  }

  /**
   * Installs a NPM package
   * @param string $packageName
   * @param string $packageVersionConstraint: everything after the @
   * @see https://docs.npmjs.com/cli/v10/configuring-npm/package-json
   * @return void
   */
  public function installNpmPackage(
    string $packageName,
    string $packageVersionConstraint = null
  ): void
  {
    //package
    $command = sprintf(
      '%s/npm.sh i %s',
      ABS_PATH_TO_ROOT,
      $packageName,
    );
    //version
    if($packageVersionConstraint) {
      $command = sprintf(
        '%s@"%s"',
        $command,
        $packageVersionConstraint
      );
    }
    //dry run
    if($this->dryRun) {
      $command = sprintf(
        '%s --dry-run',
        $command
      );
    }
    passthru($command);
  }

  /**
   * Removess a NPM package
   * @param string $packageName
   * @see https://docs.npmjs.com/cli/v10/configuring-npm/package-json
   * @return void
   */
  public function removeNpmPackage(
    string $packageName
  ): void
  {
    //package
    $command = sprintf(
      '%s/npm.sh remove %s',
      ABS_PATH_TO_ROOT,
      $packageName,
    );
    //dry run
    if($this->dryRun) {
      $command = sprintf(
        '%s --dry-run',
        $command
      );
    }
    passthru($command);
  }
}
