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
    //check operations
    if(!isset($this->config->operations) || !is_array($this->config->operations) || empty($this->config->operations)) {
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain an \'operations\' property to define possible model API operations', $configPath, getInstanceNamespace($this)));
    } else {
      foreach ($this->config->operations as $operationName => $definition) {
        //each operation must have at least a methond and an endpoint
        if(!isset($definition->method) || !$definition->method || !isset($definition->uri) || !$definition->uri) {
          throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s contains a \'%s\' operation without valid \'method\' and/or \'uri\' property', $configPath, getInstanceNamespace($this), $operationName));
        }
      }
    }
  }

  /**
  * Checks whether configuration for a certain operation has been defined
  * @param string $operationName
  * @throws \Exception if operation is not configured
  **/
  protected function getOperationConfiguration(string $operationName): object
  {
    if(!isset($this->getConfig()->operations[$operationName])) {
      $configPath = $this->buildConfigPath();
      throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'%s\' operation', $configPath, getInstanceNamespace($this), $operationName));
    } else {
      return $this->getConfig()->operations[$operationName];
    }
  }

  /**
  * Builds request headers
  * @return array
  **/
  abstract protected function buildHeaders(): array;

  /**
  * Makes a request
  * @param string $method
  * @param string $request
  * @param object $body
  * @return mixed json response or false in case of error
  **/
  protected function makeRequest(string $method, string $request, $body = null)
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
      /*if(ENVIRONMENT == 'development') {
        x($e);
        x($url);
        x($method);
        x($headers);
        x($body);
        x($e->getMessage(),1);
        xx($e->getResponse()->getBody()->getContents(),1);
      } else {
        xx($e->getMessage(),1);
      }*/
      return (object) [
        'error' => true,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Builds where guessing standard behaviour, to be overriden when necessary
   * @param string $operation
   * @param string $url
   * @param array $where: where conditions, array of arrays, each contains field name and field value
   */
  public function buildWhere(string $operation, string $url, array $where = [])
  {
    $operationConfig = $this->getOperationConfiguration('get');
    $urlParameters = [];
    foreach($where as $field) {
      list($fieldName, $fieldValue) = $field;
      //primary key added as url token
      if($fieldName == $this->getconfig()->primaryKey) {
        $url = $this->addPrimaryKeyValueToUrl($url, $field[1]);
      }
      //url parameter
      if(isset($operationConfig->urlParameters) && in_array($fieldName, $operationConfig->urlParameters)) {
        $urlParameters[$fieldName] = $fieldValue;
      }
    }
    if(!empty($urlParameters)) {
      $url .= '?' . http_build_query($urlParameters);
    }
    return $url;
  }

  /**
   * Builds where guessing standard behaviour, to be overriden when necessary
   * @param string $url
   * @param mixed $primaryKeyValue
   * @return string
   */
  protected function addPrimaryKeyValueToUrl(string $url, $primaryKeyValue): string
  {
    return $url .= '/' . $primaryKeyValue;
  }

  /**
   * Gets a recordset, force derived class to implement it's own API client dependent method
   * @param array $where: filter conditions, see buildWhere() method for details
   * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC') 
   *              NOTE: only first element is considered, that is sorting is performed over only one field at a time
   * @param int $limit: passed from Controller but handled directly into table template (because Finder doesn't offer internal limit and the result is not processed here to save memory but passed directly to template)
   * @param array $extraFields: no effect
   */
  public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = []): iterable|object
  {
    //check configuration
    $operationConfig = $this->getOperationConfiguration('get');
    //build url
    $url = $operationConfig->uri;
    if(!empty($where)) {
      $url = $this->buildWhere('get', $url, $where);
    }
    //request
    $response = $this->makeRequest(
      $operationConfig->method,
      $url
    );
    //response
    return isset($operationConfig->responseProperty) && $operationConfig->responseProperty && isset($response->{$operationConfig->responseProperty}) ? $response->{$operationConfig->responseProperty} : $response;
  }

  /**
   * Gets a record
   * @param array $where: where conditions, see buildWhere() method for details
   */
  public function first(array $where = [])
  {
    $records = $this->get($where);
    return is_array($records) ? reset($records) : $records;
  }

  /**
   * Insert a record
   * @param object $fieldsValues
   */
  public function insert(object &$fieldsValues = new \stdClass)
  {
    //check configuration
    $operationConfig = $this->getOperationConfiguration('insert');
    //build url
    $url = $operationConfig->uri;
    //request
    $response = $this->makeRequest(
      $operationConfig->method,
      $url,
      (object) $fieldsValues
    );
    return isset($operationConfig->responseProperty) && $operationConfig->responseProperty && isset($response->{$operationConfig->responseProperty}) ? $response->{$operationConfig->responseProperty} : $response;
  }

  /**
   * Updates a record
   * @param mixed $primaryKeyValue
   * @param object $fieldsValues
   */
  public function update($primaryKeyValue = null, object &$fieldsValues = new \stdClass)
  {
    //check configuration
    $operationConfig = $this->getOperationConfiguration('update');
    //build url
    $url = $operationConfig->uri;
    //primary key value
    if($primaryKeyValue) {
      $url = $this->addPrimaryKeyValueToUrl($url, $primaryKeyValue);
    }
    //request
    $response = $this->makeRequest(
      $operationConfig->method,
      $url,
      (object) $fieldsValues
    );
    return isset($operationConfig->responseProperty) && $operationConfig->responseProperty && isset($response->{$operationConfig->responseProperty}) ? $response->{$operationConfig->responseProperty} : $response;
  }

  /**
   * Deletes a record
   * @param mixed $primaryKeyValue
   */
  public function delete($primaryKeyValue)
  {
    //check configuration
    $operationConfig = $this->getOperationConfiguration('delete');
    //build url
    $url = $operationConfig->uri;
    //primary key value
    $url = $this->addPrimaryKeyValueToUrl($url, $primaryKeyValue);
    //request
    $response = $this->makeRequest(
      $operationConfig->method,
      $url
    );
  }

  /**
   * Handles an exception using error codes (see https://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm)
   * @param Exception $exception
   * @return object to be used for alert display with the following properties:
   *   ->code: alphanumeric message code
   *   ->data: an array with any specific error code relevant data (such as involved field names)
   */
  public function handleException(\Exception $exception): object
  {
    //get error code and message
    $errorCode = (string) $exception->getCode();
    $errorMessage = $exception->getMessage();
    $code = null;
    $data = null;
    $rawMessage = sprintf('error code: %s; error message: %s', $errorCode, $errorMessage);
    return (object) [
      'erroCode' => $errorCode,
      'code' => $code,
      'data' => $data,
      'rawMessage' => $rawMessage
    ];
  }
}
