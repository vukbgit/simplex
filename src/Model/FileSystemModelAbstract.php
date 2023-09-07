<?php
declare(strict_types=1);

namespace Simplex\Model;

use function Simplex\getInstanceNamespace;

/*
 * class that rapresents a model based on filesystem
 */
abstract class FileSystemModelAbstract extends BaseModelAbstract
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('fileSystem');
  }
  
  /**
   * Parses config
   */
  protected function parseConfig()
  {
    $configPath = $this->buildConfigPath();
    //check root folder
    if(!isset($this->config->rootFolder) || !is_dir($this->config->rootFolder)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'rootFolder\' property and it must be a valid path', $configPath, getInstanceNamespace($this)));
    }
  }
  
  /**
   * Gets a recordset
   * @param array $where: files mask, defaults to ['*']
   * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC') 
   *              NOTE: only first element is considered, that is sorting is performed over only one field at a time
   * @param int $limit: passed from Controller but handled directly into table template (because Finder doesn't offer internal limit and the result is not processed here to save memory but passed directly to template)
   * @param array $extraFields: no effect
   */
  public function get(array $masks = [], array $order = [], int $limit = null, array $extraFields = []): iterable
  {
    //path to folder
    $pathTofolder = $this->config->rootFolder;
    $subFolder = filter_input(INPUT_GET, 'sf', FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
    if($subFolder) {
      $pathTofolder .= $subFolder;
    }
    //iterator
    $directoryIterator = new \FilesystemIterator($pathTofolder, \FilesystemIterator::SKIP_DOTS);
    //masks
    if(empty($masks)) {
      $masks = [
        'name' => [
          '*'
        ]
      ];
    }
    $findAll = count($masks) === 1 && isset($masks['name']) && $masks['name'][0] == '*';
    $directoryIterator = new \CallbackFilterIterator(
      $directoryIterator,
      function ($entry) use ($masks, $findAll) {
        return (
          //check if folder / file
          (
            ($entry->isDir() && (!isset($this->config->findFolders) || $this->config->findFolders))
            ||
            ($entry->isFile() && (!isset($this->config->findFiles) || $this->config->findFiles))
          )
          &&
          //masks
          (
            $findAll
            ||
            call_user_func(
              function() use ($entry, $masks){
                //all of criteria must be satisfied
                $include = true;
                foreach($masks as $criterion => $criterionMasks) {
                  foreach($criterionMasks as $mask) {
                    //$mask can be a string or an array where first element is the value and the second one an operator
                    if(is_string($mask)) {
                      $value = $mask;
                      $operator = '=';
                    } elseif(is_array($mask)) {
                      $value = $mask[0];
                      $operator = $mask[1];
                    }
                    switch ($criterion) {
                      //file name
                      case 'name':
                        switch ($operator) {
                          //equality
                          case '=':
                            if(!fnmatch($value, $entry->getBasename(), FNM_CASEFOLD)) {
                              $include = false;
                            }
                            break;
                        }
                        break;
                      //file extension
                      case 'extension':
                        switch ($operator) {
                          //equality
                          case '=':
                            if($value != $entry->getExtension()) {
                              $include = false;
                            }
                            break;
                        }
                        break;
                      //file modification time, assume comparison just between dates
                      case 'mTime':
                        $entryDate = new \DateTimeImmutable('@' . $entry->getMTime());
                        $valueDate = new \DateTimeImmutable($value);
                        switch ($operator) {
                          //equality
                          case '=':
                            if($entryDate->format('Y-m-d') != $valueDate->format('Y-m-d')) {
                              $include = false;
                            }
                            break;
                          //less than
                          case '<':
                            if($entryDate->format('Y-m-d') >= $valueDate->format('Y-m-d')) {
                              $include = false;
                            }
                            break;
                          //less than or equal
                          case '<=':
                            if($entryDate->format('Y-m-d') > $valueDate->format('Y-m-d')) {
                              $include = false;
                            }
                            break;
                          //more than
                          case '>':
                            if($entryDate->format('Y-m-d') <= $valueDate->format('Y-m-d')) {
                              $include = false;
                            }
                            break;
                          //more than or equal
                          case '>=':
                            if($entryDate->format('Y-m-d') < $valueDate->format('Y-m-d')) {
                              $include = false;
                            }
                            break;
                        }
                        break;
                      //file size
                      case 'size':
                        $entrySize = $entry->getSize();
                        //in bytes
                        $valueSize = (int) $value;
                        switch ($operator) {
                          //equality
                          case '=':
                            if($entrySize != $valueSize) {
                              $include = false;
                            }
                            break;
                          //less than
                          case '<':
                            if($entrySize >= $valueSize) {
                              $include = false;
                            }
                            break;
                          //less than or equal
                          case '<=':
                            if($entrySize > $valueSize) {
                              $include = false;
                            }
                            break;
                          //more than
                          case '>':
                            if($entrySize <= $valueSize) {
                              $include = false;
                            }
                            break;
                          //more than or equal
                          case '>=':
                            if($entrySize < $valueSize) {
                              $include = false;
                            }
                            break;
                        }
                        break;
                    }
                    
                  }
                }
                return $include;
              }
            )
          )
        );
      }
    );
    //limit
    $limit = (int) $limit;
    if($limit) {
      $directoryIterator = new \LimitIterator($directoryIterator, 0, $limit);
    }
    //sort
    if(!empty($order)) {
      $sortField = $order[0];
      $fieldName = $sortField[0];
      $direction = $sortField[1];
      $directoryIterator = $this->sortEntries($directoryIterator, $fieldName, $direction);
    }
    return $directoryIterator;
  }
  
  /**
   * Sorts entry
   * @param iterable $iterator
   * @param string $compareCriterion
   * @param string $direction
   */
  protected function sortEntries(iterable $iterator, string $compareCriterion, string $direction)
  {
    $sortedEntries = new \ArrayObject();
    foreach( $iterator as $item )
    {
      $sortedEntries->append($item);
    }
    $compareMethod = sprintf('compareEntriesBy%s%s', ucfirst($compareCriterion), ucfirst(strtolower($direction)));
    $sortedEntries->uasort([$this, $compareMethod]);
    return $sortedEntries->getIterator();
  }
  
  /**
   * Compare entries by type ascending (folders firts, then files)
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   * @return int
   *  -1 $splFileInfo1 before $splFileInfo2
   *   0 $splFileInfo1 same as $splFileInfo2
   *   1 $splFileInfo1 after $splFileInfo2
   */
  protected function compareEntriesByTypeAsc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    $splFileInfo1Type = $splFileInfo1->getType();
    $splFileInfo2Type = $splFileInfo2->getType();
    if($splFileInfo1Type == $splFileInfo2Type) {
      return 0;
    } else {
      if($splFileInfo1Type == 'dir') {
        return -1;
      } else {
        return 1;
      }
    }
  }
  
  /**
   * Compare entries by type descending (files firts, then folders)
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   * @return int
   *  -1 $splFileInfo2 before $splFileInfo1
   *   0 $splFileInfo1 same as $splFileInfo2
   *   1 $splFileInfo2 after $splFileInfo1
   */
  protected function compareEntriesByTypeDesc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    $splFileInfo1Type = $splFileInfo1->getType();
    $splFileInfo2Type = $splFileInfo2->getType();
    if($splFileInfo1Type == $splFileInfo2Type) {
      return 0;
    } else {
      if($splFileInfo1Type == 'dir') {
        return 1;
      } else {
        return -1;
      }
    }
  }
  
  /**
   * Compare entries by name ascending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   * @return int
   *  -1 $splFileInfo1 before $splFileInfo2
   *   0 $splFileInfo1 same as $splFileInfo2
   *   1 $splFileInfo1 after $splFileInfo2
   */
  protected function compareEntriesByNameAsc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return strcmp($splFileInfo1->getFileName(), $splFileInfo2->getFileName());
  }
  
  /**
   * Compare entries by name descending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   * @return int
   *  -1 $splFileInfo2 before $splFileInfo1
   *   0 $splFileInfo1 same as $splFileInfo2
   *   1 $splFileInfo2 after $splFileInfo1
   */
  protected function compareEntriesByNameDesc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return strcmp($splFileInfo2->getFileName(), $splFileInfo1->getFileName() );
  }
  
  /**
   * Compare entries by type ascending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesByExtensionAsc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return strcmp($splFileInfo1->getExtension(), $splFileInfo2->getExtension());
  }
  
  /**
   * Compare entries by type descending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesByExtensionDesc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return strcmp($splFileInfo2->getExtension(), $splFileInfo1->getExtension() );
  }
  
  /**
   * Compare entries by size ascending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesBySizeAsc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return $splFileInfo1->getSize() <=> $splFileInfo2->getSize();
  }
  
  /**
   * Compare entries by size descending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesBySizeDesc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return $splFileInfo2->getSize() <=> $splFileInfo1->getSize();
  }
  
  /**
   * Compare entries by modification time ascending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesByMtimeAsc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return $splFileInfo1->getMtime() <=> $splFileInfo2->getMtime();
  }
  
  /**
   * Compare entries by modification time dedscending
   * @param \SplFileInfo $splFileInfo1
   * @param \SplFileInfo $splFileInfo2
   */
  protected function compareEntriesByMtimedesc(\SplFileInfo $splFileInfo1, \SplFileInfo $splFileInfo2)
  {
    return $splFileInfo2->getMtime() <=> $splFileInfo1->getMtime();
  }
}
