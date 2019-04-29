<?php
declare(strict_types=1);

namespace Simplex\Model;

use Simplex\PixieExtended;

/*
* class that rapresents a model, an atomic structure of data stored in a database
*/
abstract class ModelAbstract
{
    /**
    * @var PixieExtended
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
    * @param strong $configPath
    */
    public function __construct(PixieExtended $query, string $configPath)
    {
        $this->query = $query;
        $this->config = require($configPath);
    }

    /**
    * Return the view or at least table defined
    */
    public function view()
    {
        return $this->config->view ?? $this->config->table;
    }

    /**
    * Gets a recordset
    * @param array $where: array of arrays, each with 2 elements (field name and value, operator defaults to '=') or 3 elements (field name, operator, value)
    * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC')
    */
    public function get(array $where = [], array $order = [])
    {
        //table
        $this->query
            ->table($this->view());
        //where conditions
        if(!empty($where)) {
            foreach ($where as $fieldCondition) {
                call_user_func_array([$this->query, 'where'], $fieldCondition);
            }
        }
        //order
        if(!empty($order)) {
            foreach ($order as $orderCondition) {
                call_user_func_array([$this->query, 'orderBy'], $orderCondition);
            }
        }
        return $this->query->get();
    }
    
    /**
    * Gets a record
    * @param array $where: array of arrays, each with 2 elements (field name and value, operator defaults to '=') or 3 elements (field name, operator, value)
    */
    public function first(array $where = [])
    {
        return current($this->get($where));
    }
}
