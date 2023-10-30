<?php
declare(strict_types=1);

namespace Simplex\Model;

use Simplex\PixieExtended;
use Simplex\PixieConnectionExtended;
use function Simplex\getInstanceNamespace;
use function Simplex\getInstancePath;

/**
* class that rapresents an abstract universal model model to be used as foundation of any other model
*/
abstract class BaseModelAbstract implements ModelInterface
{
  /**
   * possible data sources
   */
  private const POSSIBLESOURCES = ['database', 'fileSystem', 'api'];
  
  /**
    * @var string data source
    */
  protected $source;
  
  /**
    * @var bool whether model has a database as source
    */
  public $hasDb;
  
  /**
    * @var bool whether model has a file system as source
    */
  public $hasFs;

  /**
    * @var bool whether model has an external API as source
    */
  public $hasApi;
  
  /**
    * @var object
    * configuration object for model
    */
  protected $config;


  
  /**
    * Constructor
    * @param string $source
    */
  public function __construct(string $source)
  {
    if(!in_array($source, self::POSSIBLESOURCES)) {
      throw new \Exception(sprintf('model class %s source property value "%s" is not valid', getInstanceNamespace($this), $source));
    } else {
      $this->source = $source;
      $this->hasDb = $this->source === 'database';
      $this->hasFs = $this->source === 'fileSystem';
      $this->hasApi = $this->source === 'api';
      $this->loadConfig();
    }
  }
  
  /*********
  * CONFIG *
  *********/
  
  /**
   * Builds path to config file
   */
  protected function buildConfigPath()
  {
    return sprintf('%s/config/model.php', getInstancePath($this));
  }
  
  /**
   * Loads and check config
   */
  private function loadConfig()
  {
    //config file must be into class-folder/config/model.php
    $configPath = $this->buildConfigPath();
    //check path
    if(!is_file($configPath)) {
      throw new \Exception(sprintf('configuration file \'%s\' for model %s is not a valid path', $configPath, getInstanceNamespace($this)));
    }
    $config = require($configPath);
    //check that config is an object
    if(!is_object($config)) {
      throw new \Exception(sprintf('configuration file \'%s\' for model %s must return an object', $configPath, getInstanceNamespace($this)));
    }
    //store config
    $this->config = $config;
    //parse config according to source
    $this->parseConfig();
  }
  
  /**
   * Parses config
   */
  protected  abstract function parseConfig();
  
  /**
   * Returns the config object
   */
  public function getConfig(): object
  {
    return $this->config;
  }
}
