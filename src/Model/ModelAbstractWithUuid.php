<?php
declare(strict_types=1);

namespace Simplex\Model;

use Simplex\PixieExtended;

/*
* class that rapresents a model, an atomic structure of data stored in a database
*/
abstract class ModelAbstractWithUuid extends ModelAbstract
{
    /********
    * INSERT *
    ********/
    
    /**
    * Inserts a record
    * @param array $fieldsValues: indexes are fields names, values are fields values, it can be an array of arrays in case of batch insert
    * @return mixed primary key of inserted records or array in case of batch insert
    */
    public function insert(array &$fieldsValues)
    {
      //values are not indexed array -> batch insert
      $batchInsert = array_is_list($fieldsValues);
      //values are associative array -> not batch insert
      if(!$batchInsert) {
        $primaryKeyValue = $this->query->query('SELECT UUID() AS uuid')->get()[0]->uuid;
        $fieldsValues[$this->config->primaryKey]  = $primaryKeyValue;
      } else {
      //in case of batch insert default primary key value should be set to uuid()
        $primaryKeyValue = null;
      }
      //geometry fields
      $this->buildGeometryFieldsSaveSql($fieldsValues);
      //insert record(s)
      $this->query
        ->table($this->table())
        ->insert($fieldsValues);
      return $primaryKeyValue;
    }
}
