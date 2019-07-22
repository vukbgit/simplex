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
    public function whereLogical(array $whereCondition): QueryBuilderHandler
    {
        //extract logical operator if any
        if(isset($whereCondition['logical'])) {
            $joiner = $whereCondition['logical'];
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
        return $this->whereHandler($key, $operator, $value, $joiner);
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
                    if(!isset($fieldConditions['logical']) || strtoupper($fieldConditions['logical']) == 'OR') {
                        $whereMethod = 'orWhere';
                    } else {
                        $whereMethod = 'where';
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
}
