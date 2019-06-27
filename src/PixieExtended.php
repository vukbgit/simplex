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
}
