<?php
declare(strict_types=1);

namespace Simplex\Model;

use Nette\Utils\Finder;
use function Simplex\getInstanceNamespace;

/*
 * class that rapresents a model based on filesystem
 */
abstract class FileSystemModelAbstract extends BaseModelAbstract
{
  /**
   * @var Finder
   */
  protected $fileBrowser;
    
  /**
   * Constructor
   * @param Finder $fileBrowser
   */
  public function __construct($fileBrowser)
  {
    parent::__construct('fileSystem');
    $this->fileBrowser = $fileBrowser;
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
  public function get(array $masks = ['*'], array $order = [], int $limit = null, array $extraFields = []): Finder
  {
    //path to folder
    $pathTofolder = $this->config->rootFolder;
    $subFolder = filter_input(INPUT_GET, 'sf', FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
    if($subFolder) {
      $pathTofolder .= $subFolder;
    }
    $this->fileBrowser = $this->fileBrowser->in($pathTofolder);
    //masks
    if(empty($masks)) {
      $masks = ['*'];
    }
    //find folders and files
    if(!isset($this->config->findFolders) || $this->config->findFolders) {
      $this->fileBrowser = $this->fileBrowser->directories()
        ->filter(function($fileInfo) use ($masks){
            foreach($masks as $mask) {
              if(!fnmatch($mask, $fileInfo->getBasename(), FNM_CASEFOLD)) {
                return false;
              }
            }
            return true;
          });
    }
    if(!isset($this->config->findFiles) || $this->config->findFiles) {
      $this->fileBrowser = $this->fileBrowser->files()
        ->filter(function($fileInfo) use ($masks){
          foreach($masks as $mask) {
            if(!fnmatch($mask, $fileInfo->getBasename(), FNM_CASEFOLD)) {
              return false;
            }
          }
          return true;
        });
    }
    //sorting
    if(!empty($order)) {
      $sortField = $order[0];
      $this->fileBrowser->sortBy(function($fileInfoA, $fileInfoB) use ($sortField) {
        $fieldName = $sortField[0];
        $direction = $sortField[1];
        switch($direction) {
          case  'DESC':
            $fileInfo1 = $fileInfoB;
            $fileInfo2 = $fileInfoA;
            break;
          case  'ASC':
          default:
            $fileInfo1 = $fileInfoA;
            $fileInfo2 = $fileInfoB;
            break;
        }
        switch ($fieldName) {
          //file name
          case 'name':
              return strcasecmp($fileInfo1->getBasename(), $fileInfo2->getBasename());
            break;
          //file type
          case 'name':
              return strcasecmp($fileInfo1->getType(), $fileInfo2->getType());
            break;
          //file size
          case 'size':
              return $fileInfo1->getSize() <=> $fileInfo2->getSize();
            break;
          //file last modification date
          case 'mTime':
            return $fileInfo1->getMTime() <=> $fileInfo2->getMTime();
            break;
        }
        
      });
    }
    return $this->fileBrowser;
  }
}
