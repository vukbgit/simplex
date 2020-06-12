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
    * Checks whether connection is alive
    * @return bool
    **/
    private function isConnectionAlive()
    {
        try {
            $this->getConnection()->getPdoInstance()->query('SELECT 1');
        } catch (PDOException $e) {
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
    
    /**
    * Builds a where condition with the possibility to pass also the logical operator 
    * @param array $whereCondition and array with 
    **/
    public function whereLogical(array $whereCondition)
    {
        //extract logical operator if any
        if(isset($whereCondition['logical'])) {
            $joiner = $whereCondition['logical'];
            unset($whereCondition['logical']);
        } else {
            $joiner = 'AND';
        }
        $key = $whereCondition[0];
        // If two params are given then assume operator is =
        if (count($whereCondition) == 2) {
            $value = $whereCondition[1];
            $operator = '=';
        } else {
            $operator = $whereCondition[1];
            $value = $whereCondition[2];
        }
        $this->whereHandler($key, $operator, $value, $joiner);
    }
    
    /**
    * Builds where conditions
    * @param array $where: array of arrays, each with 2 number indexed elements (field name and value, comparison operator defaults to '=') or 3 number indexed elements (field name, comparison operator, value)
    *   + an optional key 'logical' (string) whose value triggers the logical operators 'AND' (default) and 'OR' 
    *   + an optional key 'grouped' (boolean) to create a grouped where condition, in this case there must be an array for each field composed as above (except for the 'grouped' key, only one nested level is allowed at this time)
    **/
    public function buildWhere(array $where)
    {
        if(!empty($where)) {
            foreach ($where as $fieldConditions) {
                //not a grouped wwhere
                if(!isset($fieldConditions['grouped']) || $fieldConditions['grouped'] === false) {
                    call_user_func([$this, 'whereLogical'], $fieldConditions);
                } else {
                //grouped where
                    //check logical operator
                    if(!isset($fieldConditions['logical']) || strtoupper($fieldConditions['logical']) == 'AND') {
                        $whereMethod = 'where';
                    } else {
                        $whereMethod = 'orWhere';
                    }
                    //clean up
                    unset($fieldConditions['grouped']);
                    unset($fieldConditions['logical']);
                    //build grouped where
                    $this->$whereMethod(function($q) use ($fieldConditions) {
                        foreach ($fieldConditions as $fieldCondition) {
                            //check logical operator
                            if(!isset($fieldCondition['logical']) || strtoupper($fieldCondition['logical']) == 'OR') {
                                $whereMethod = 'orWhere';
                            } else {
                                $whereMethod = 'where';
                            }
                            unset($fieldCondition['logical']);
                            call_user_func_array([$q, $whereMethod], $fieldCondition);
                        }
                    });
                }
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
        //x('get');
        $this->checkConnection();
        return parent::get();
    }
}
