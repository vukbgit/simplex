<?php
declare(strict_types=1);

namespace Simplex;

use \Pixie\QueryBuilder\QueryBuilderHandler;

/*
* Subclass of the gorgeous Pixie query builder (https://github.com/usmanhalalit/pixie) to add some functionalities
*
*/
class PixieExtended extends QueryBuilderHandler
{
    /**
     * whether to check connection to database on every Simplex\Model select, insert, update and delete operation, usefull for long lived scripts (i.e. websocket server)
     */
     private $checkConnection = false;
    
     /**
     * @var array
     * driver dependent options
     */
     private $optionsByDriver = [
         'labelDelimiter' => [
             'mysql' => '`',
             'pgsql' => '"'
         ],
         'caseInsensitiveLikeOperator' => [
             'mysql' => 'LIKE',
             'pgsql' => 'ILIKE'
         ],
         'likeOperatorTextCastDataType' => [
             'mysql' => 'CHAR',
             'pgsql' => 'TEXT'
         ],
     ];
     
     /**
      * query object being built
      */
      private $buildingQuery;
     
    /**
    * Checks whether connection is alive
    * @return bool
    **/
    public function getDriverOption($optionName)
    {
        return $this->optionsByDriver[$optionName][$this->getConnection()->getAdapter()] ?? null;
    }
    
    /**
    * Checks whether connection is alive
    * @return bool
    **/
    private function isConnectionAlive()
    {
        try {
            $this->pdo()->query('SELECT 1');
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
    * Checks whether connection is alive and reconnects if necessary
    * @return bool
    **/
    private function checkConnection()
    {
        if($this->checkConnection === false) {
            return;
        }
        if(!$this->isConnectionAlive()) {
            $this->connection->reconnect();
            $this->pdo = $this->connection->getPdoInstance();
        }
    }
    
    /**
    * Sets whether to check if connection is alive and reconnects if necessary
    * it can be set at runtime through container for *all* of classes the use a PixieExtend, i.e in a controller: $this->DIContainer->get('queryBuilder')->setCheckConnection(true);
    * @param bool $set
    **/
    public function setCheckConnection(bool $set)
    {
        $this->checkConnection = $set;
    }
    
    /**
    * Resets pdo statement
    **/
    public function resetPdoStatement()
    {
        $this->pdoStatement = null;
    }
    
    /**
    * Sets table
    * @param string $table
    **/
    public function table($table): QueryBuilderHandler
    {
        //the table() method return a static instance of the querybuilder handler
        $queryBuilderHandler = parent::table($table);
        //fetch the statement and pass to current instance
        $this->statements = $queryBuilderHandler->getStatements();
        //return $queryBuilderHandler;
        return $this;
    }

    /**
    * Gets raw sql code
    **/
    public function sql(): string
    {
        return $this->getQuery()->getRawSql();
    }

    /**
    * Checks whether a table exists
    * @param string $table
    **/
    public function tableExists(string $table): bool
    {
        return !is_null($this->query(sprintf("SHOW TABLES LIKE '%s'", $table))->first());
    }
    
    private function buildFieldWhere($fieldConditions, $query) {
      if(isset($fieldConditions['grouped'])) {
        unset($fieldConditions['grouped']);
        $whereMethod = 'where';
        //logical: AND|OR|NOT|AND NOT|OR NOT
        if(isset($fieldConditions['logical'])) {
          $logical = strtoupper($fieldConditions['logical']);
          switch($logical) {
            case 'OR':
              $whereMethod = 'orWhere';
            break;
            case 'NOT':
            case 'AND NOT':
              $whereMethod = 'whereNot';
            break;
            case 'OR NOT':
              $whereMethod = 'orWhereNot';
            break;
            //AND = where
            default:
              $whereMethod = 'where';
            break;
          }
          unset($fieldConditions['logical']);
        }
        $query->$whereMethod(function($q) use ($fieldConditions) {
          foreach ($fieldConditions as $fieldCondition) {
            $this->buildFieldWhere($fieldCondition, $q);
          }
        });
      } else {
        //defaults
        $key = $fieldConditions[0];
        $joiner = 'AND';
        //logical: AND|OR|NOT|AND NOT|OR NOT
        if(isset($fieldConditions['logical'])) {
          $joiner = strtoupper($fieldConditions['logical']);
          //correct NOT alone
          if($joiner === 'NOT') {
            $joiner = 'AND NOT';
          }
          unset($fieldConditions['logical']);
        }
        //operator
        //NOTE: for NULL operator to work a 3rd element MUST be passed with any (ignored) value
        if (count($fieldConditions) == 1 && $fieldConditions[0] instanceof \Pixie\QueryBuilder\Raw) {
          $key = $fieldConditions[0];
          $operator = null;
          $value = null;
        } elseif (count($fieldConditions) == 2) {
          $value = $fieldConditions[1];
          $operator = '=';
        } else {
          $value = $fieldConditions[2];
          $operator = strtoupper($fieldConditions[1]);
        }
        //build condition
        switch($operator) {
          case 'NULL':
          case 'ISNULL':
            $joiner = trim(str_replace('NOT', '', $joiner, $countNegation));
            //AND joiner => ''
            if($joiner == 'AND') {
              $joiner = '';
            }
            $negation = $countNegation ? 'NOT' : '';
            $query->whereNullHandler($key, $negation, $joiner);
          break;
          default:
            $query->whereHandler($key, $operator, $value, $joiner);
          break;
        }
      }
    }
    
    /**
    * Builds where conditions
    * @param array $where: array of arrays, each with 2 number indexed elements (field name and value, comparison operator defaults to '=') or 3 number indexed elements (field name, comparison operator, value); comparison operator can be NULL to test 'IS NULL'
    *   + an optional key 'logical' (string) whose value triggers the logical operators 'AND' (default) and 'OR' 
    *   + an optional key 'grouped' (boolean) to create a grouped where condition, in this case there must be an array for each field composed as above
    [
      // default operator =, logical AND
      [
        field-name,
        field-value
      ]
      // custom operator, logical AND
      [
        field-name,
        operator,
        field-value
      ]
      // default operator =, logical OR
      [
        field-name,
        field-value,
        'logical' => 'OR'
      ]
      // custom operator, logical OR
      [
        field-name,
        operator,
        field-value,
        'logical' => 'OR'
      ]
      //group
      [
        'grouped' => true
        'logical' => 'AND|OR'
        [ field 1 definition as above ],
        [ field 2 definition as above ],
        ...
      ]
    ]
    **/
    public function buildWhere(array $where)
    {
      $this->buildingQuery = $this;
      if(!empty($where)) {
        foreach ($where as $fieldConditions) {
          $this->buildFieldWhere($fieldConditions, $this);
        }      
      }
    }
    
    /**
     * Get all rows
     *
     * @return \stdClass|array
     * @throws Exception
     */
    public function get()
    {
        $this->checkConnection();
        return parent::get();
    }
}
