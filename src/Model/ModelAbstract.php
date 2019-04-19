<?php
declare(strict_types=1);

namespace Simplex\Model;

use \Pixie\QueryBuilder\QueryBuilderHandler;

/*
* class that rapresents a model, an atomic structure of data stored in a database
*/
abstract class ModelAbstract
{
    /**
    * @var QueryBuilderHandler
    */
    protected $query;

    /**
    * @var object
    * configuration object for model
    */
    protected $config;

    /**
    * Constructor
    * @param QueryBuilderHandler $query
    * @param object $config
    */
    public function __construct(QueryBuilderHandler $query, \stdClass $config)
    {
        $this->query = $query;
        $this->config = $config;
    }

    /**
    * get complete list
    */
    public function getList()
    {
        return $this->query
            ->table($this->config->table)
            ->get();

    }
}
