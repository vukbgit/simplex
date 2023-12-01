<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
$refactor->setDryRun(false);

//Simplex\Model\ModelAbstract::get() override update
$gets = $refactor->searchPatternManipulateFiles(
  'replace',
  'public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = []): array',
  ['private/local'],
  [],
  ['Model.php'],
  'public function get(array $where = [], array $order = [], int $limit = null, array $extraFields = [], string $view = \'\'): array'
);
