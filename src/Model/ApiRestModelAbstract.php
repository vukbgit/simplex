<?php
declare(strict_types=1);

namespace Simplex\Model;

use GuzzleHttp;
use function Simplex\getInstanceNamespace;

/*
 * class that rapresents a model based on an API
 */
abstract class ApiRestModelAbstract extends ApiModelAbstract
{
  /**
   * GuzzleHttp\ClientInterface
   **/
  protected $client;

  /**
   * Constructor
   */
  public function __construct(GuzzleHttp\ClientInterface $client)
  {
    parent::__construct($client);
  }
  
  /**
   * Parses config
   */
  protected function parseConfig()
  {
    $configPath = $this->buildConfigPath();
    //check API endpoint
    if(!isset($this->config->endpoint)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain an \'endpoint\' property', $configPath, getInstanceNamespace($this)));
    }
    //check primary key
    if(!isset($this->config->primaryKey)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'primaryKey\' property', $configPath, getInstanceNamespace($this), $configPath));
    }
    //check URIs
    if(!isset($this->config->getUri)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'getUri\' property (to retrieve list of items)', $configPath, getInstanceNamespace($this)));
    }
  }

  /**
  * Builds request headers
  * @return array
  **/
  abstract protected function buildHeaders(): array;

  /**
  * Makes a request
  * @param string $request: l'operazione API specifica da chiamare (es: issues)
  * @param object $body
  * @param string $method: default POST perché l'API del gestionale dovrebbe funzionare solo con POST
  * @return json risposta o false in caso di errore
  **/
  protected function makeRequest(string $request, $body = null, string $method = 'GET')
  {
    $url = sprintf('%s%s', $this->getConfig()->endpoint, $request);
    $headers = $this->buildHeaders();
    $body = json_encode($body);
    try {
        $response = $this->client->request(
          $method,
          $url,
          [
            'headers' => $headers,
            'body' => $body
          ]
        );
        return json_decode($response->getBody()->getContents());
    } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException | \GuzzleHttp\Exception\ClientException $e) {
      if(ENVIRONMENT == 'development') {
        x($url);
        x($method);
        x($headers);
        x($body);
        x($e->getMessage(),1);
        x($e->getResponse()->getBody()->getContents(),1);
      }
      return false;
    }
  }
  
  /**
   * Gets a recordset, force derived class to implement it's own API client dependent method
   * @param array $where: filter conditions
   * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC') 
   *              NOTE: only first element is considered, that is sorting is performed over only one field at a time
   * @param int $limit: passed from Controller but handled directly into table template (because Finder doesn't offer internal limit and the result is not processed here to save memory but passed directly to template)
   * @param array $extraFields: no effect
   */
  public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = []): iterable
  {
    $url = $this->getConfig()->getUri;
    $response = $this->makeRequest(
      $url
    );
    return $this->getConfig()->getUriProperty ? $response->{$this->getConfig()->getUriProperty} : $response;
  }

  /**
   * Gets a record
   * @param array $where: where conditions, see get() method for details
   */
  public function first(array $where = [])
  {
      return current($this->get($where));
  }
}
