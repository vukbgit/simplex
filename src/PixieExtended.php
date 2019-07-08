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
}
