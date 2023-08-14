<?php
declare(strict_types=1);

namespace Simplex\Model;

// Declare the interface 'Template'
interface ModelInterface
{
  /**
   * Gets a recordset
   * @param array $where: filter conditions
   * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC')
   * @param int $limit
   * @param array $extraFields:
   *                for database model = any other field to get in addition to the ones defined into table/view for example:
   *                for filesystem model = no effect
   */
  public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = []): iterable;
} 
