<?php
declare(strict_types=1);

namespace Simplex\Model;

/*
 * class that rapresents a model based on an API
 */
abstract class ApiModelAbstract extends BaseModelAbstract
{
  /**
   * The client instance used to connect to the API
   * object
   **/
  protected $client;


  /**
   * Constructor
   */
  public function __construct(object $client)
  {
    parent::__construct('api');
    $this->client = $client;
  }
  
  /**
   * Parses config
   */
  protected function parseConfig()
  {
    $configPath = $this->buildConfigPath();
    //check root folder
    /*if(!isset($this->config->rootFolder) || !is_dir($this->config->rootFolder)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'rootFolder\' property and it must be a valid path', $configPath, getInstanceNamespace($this)));
    }*/
  }
  
  /**
   * Gets a recordset, force derived class to implement it's own API client dependent method
   * @param array $where: filter conditions
   * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC') 
   *              NOTE: only first element is considered, that is sorting is performed over only one field at a time
   * @param int $limit: passed from Controller but handled directly into table template (because Finder doesn't offer internal limit and the result is not processed here to save memory but passed directly to template)
   * @param array $extraFields: no effect
   */
  abstract public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = []): iterable;

}
