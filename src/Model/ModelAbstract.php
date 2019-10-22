<?php
declare(strict_types=1);

namespace Simplex\Model;

use Simplex\PixieExtended;
use function Simplex\getInstanceNamespace;
use function Simplex\getInstancePath;
use function Simplex\loadLanguages;

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
    * @param string $configPath
    */
    public function __construct(PixieExtended $query)
    {
        $this->query = $query;
        $this->loadConfig();
    }

    /*********
    * CONFIG *
    *********/

    /**
    * Loads and check config
    */
    private function loadConfig()
    {
        //config file must be into class-folder/config/model.php
        $configPath = sprintf('%s/config/model.php', getInstancePath($this));
        //check path
        if(!is_file($configPath)) {
            throw new \Exception(sprintf('configuration file \'%s\' for model %s is not a valid path', $configPath, getInstanceNamespace($this)));
        }
        $config = require($configPath);
        //check that config is an object
        if(!is_object($config)) {
            throw new \Exception(sprintf('configuration file \'%s\' for model %s must return an object', $configPath, getInstanceNamespace($this)));
        }
        //check table
        if(!isset($config->table)) {
            throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'table\' property', $configPath, getInstanceNamespace($this)));
        }
        //check primary key
        if(!isset($config->primaryKey)) {
            throw new \Exception(sprintf('configuration loaded from file \'%s\' for model %s must contain a \'primaryKey\' property', $configPath, getInstanceNamespace($this)));
        }elseif(is_array($config->primaryKey)) {
            throw new \Exception(sprintf('Simplex model does not support composite primary keys, model %s configuration defined in %s must expose a single primary key', getInstanceNamespace($this), $configPath));
        }
        $this->config = $config;
    }
    
    /**
    * Returns the config object
    */
    public function getConfig(): object
    {
        return $this->config;
    }
    
    /**
    * Returns the table defined
    */
    public function table(): string
    {
        return $this->config->table;
    }
    
    /**
    * Returns the defined view or at least table
    */
    public function view(): string
    {
        return $this->config->view ? ($this->hasLocales() ? sprintf('%s_locales', $this->config->view) : $this->config->view) : $this->config->table;
    }
    
    /**
    * Returns whether the model has at least one localized field
    */
    public function hasLocales(): bool
    {
        return isset($this->config->locales);
    }
    
    /**
    * Returns whether the model has at least one upload
    */
    public function hasUploads(): bool
    {
        return isset($this->config->uploads);
    }

    /**
    * Returns the configured upload keys names
    */
    public function getUploadKeys(): array
    {
        if($this->hasUploads()) {
            return array_keys($this->config->uploads);
        } else {
            return [];
        }
    }

    /**
    * Returns the configured outputs names for an upload key
    * @param string $uploadKey
    */
    public function getUploadKeyOutputs(string $uploadKey): array
    {
        if($this->hasUploads()) {
            return array_keys($this->config->uploads[$uploadKey]);
        } else {
            return [];
        }
    }

    /*******************
    * DEBUG & MESSAGES *
    *******************/

    /**
    * Ouputs last sql
    */
    public function sql(): string
    {
        return $this->query->sql();
    }

    /**
    * Handles an exception using error codes (see https://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm)
    * @param PDOException $exception
    * @return object to be used for alert display with the following properties:
    *   ->code: alphanumeric message code
    *   ->data: an array with any specific error code relevant data (such as involved field names)
    */
    public function handleException(\PDOException $exception): object
    {
        //get error code and message
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        //extract SQL-92 error class and subclass from code
        $class = substr($errorCode, 0, 2);
        $subclass = substr($errorCode, 2);
        switch($class) {
            //Integrity constraint violation
            case '23':
                //duplicate entry
                if(preg_match('/Duplicate entry/', $errorMessage) === 1) {
                    $errorType = 'duplicate_entry';
                    //extract field name
                    preg_match("/'([0-9a-zA-Z_]+)'$/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
                //failed foreign key constraint
                if(preg_match('/a foreign key constraint fails/', $errorMessage) === 1) {
                    $errorType = 'fk_constraint';
                    //extract field name
                    preg_match("/FOREIGN KEY \(`([0-9a-zA-Z_]+)`\)/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
                //null value on mandatory column
                if(preg_match('/Column \'[0-9a-zA-Z_]+\' cannot be null/', $errorMessage) === 1) {
                    $errorType = 'mandatory_null';
                    //extract field name
                    preg_match("/Column \'([0-9a-zA-Z_]+)\'/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
            break;
            //Column not found
            case '42':
                if(preg_match('/Column not found/', $errorMessage) === 1) {
                    $errorType = 'column_not_found';
                    //extract field name
                    preg_match("/Unknown column '([0-9a-zA-Z_]+)'/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
            break;
        }
        return (object) [
            'code' => sprintf('SQLSTATE_%s_%s', $errorCode, $errorType),
            'data' => $data
        ];
    }
    
    /********************
    * FIELDS PROCESSING *
    ********************/

    /**
    * turns a date from the locale format to YYYY-MM-DD
    * @param string $fromFormat: locale format
    * @param string $date
    */
    public function formatDate(string $fromFormat, string $date): string
    {
        $date = \DateTime::createFromFormat($fromFormat, $date);
        return $date->format('Y-m-d');
    }
    
    /*********
    * SELECT *
    *********/
    
    /*********
    * SELECT *
    *********/

    /**
    * Gets a recordset
    * @param array $where: see Simplex\PixieExtended::buildWhere for details
    * @param array $order: array of arrays, each with 1 element (field name, direction defaults to 'ASC') or 2 elements (field name, order 'ASC' | 'DESC')
    */
    public function get(array $where = [], array $order = [])
    {
        //table
        $this->query
            ->table($this->view());
        //where conditions
        $this->query->buildWhere($where);
        //order
        if(!empty($order)) {
            foreach ($order as $orderCondition) {
                call_user_func_array([$this->query, 'orderBy'], $orderCondition);
            }
        }
        $records = $this->query->get();
        //localized fields
        $records = $this->extractLocales($records);
        return $records;
    }
    
    /**
    * Process locales into a recordset
    * @param array $records
    */
    public function extractLocales(array $records)
    {
        if($this->hasLocales()) {
            $recordsByPK = [];
            $languagesCodes = array_keys(get_object_vars(loadLanguages('local')));
            $localizedFieldValuesTemplate = [];
            foreach ($languagesCodes as $languageCode) {
                $localizedFieldValuesTemplate[$languageCode] = null;
            }
            foreach ($records as $record) {
                $PKValue = $record->{$this->config->primaryKey};
                //init record by PK
                if(!isset($recordsByPK[$PKValue])) {
                    $recordsByPK[$PKValue] = (object) [
                    ];
                    foreach ($record as $field => $value) {
                        //skip language code field
                        if($field == 'language_code') {
                            continue;
                        }
                        //if it's not a localized field store as is
                        if(!in_array($field, $this->config->locales)) {
                            $recordsByPK[$PKValue]->$field = $value;
                        } else {
                        //if it's a localized field init field's values container
                            $recordsByPK[$PKValue]->$field = $localizedFieldValuesTemplate;
                        }
                    }
                }
                //loop record's localized fields
                foreach ($this->config->locales as $field) {
                    $recordsByPK[$PKValue]->$field[$record->language_code] = $record->$field;
                }
            }
            $records = array_values($recordsByPK);
        }
        return $records;
    }
    
    /**
    * Gets a record
    * @param array $where: where conditions, see get() method for details
    */
    public function first(array $where = [])
    {
        return current($this->get($where));
    }
    
    /********
    * INSERT *
    ********/
    
    /**
    * Inserts a record
    * @param array $fieldsValues: indexes are fields names, values are fields values
    */
    public function insert(array $fieldsValues): string
    {
        //insert record
        $primaryKeyValue = $this->query
            ->table($this->table())
            ->insert($fieldsValues);
        return $primaryKeyValue;
    }
    
    /*********
    * UPDATE *
    *********/
    
    /**
    * Updates a record
    * @param mixed $primaryKeyValue
    * @param array $fieldsValues: indexes are fields names, values are fields values
    */
    public function update($primaryKeyValue, array $fieldsValues)
    {
        $this->query
            ->table($this->table())
            ->where($this->config->primaryKey, $primaryKeyValue)
            ->update($fieldsValues);
    }
    
    /*********
    * DELETE *
    *********/
    
    /**
    * Deletes a record by primary key value and/or other where conditions
    * @param mixed $primaryKeyValue
    * @param array $where: see Simplex\PixieExtended::buildWhere for details
    */
    public function delete($primaryKeyValue = null, $where = [])
    {
        //set whrre conditions
        if($primaryKeyValue) {
            $where = array_merge(
                $where,
                [
                    [$this->config->primaryKey, $primaryKeyValue]
                ]
            );
        }
        //uploads
        if($this->hasUploads()) {
            //get uploaded files to check for deletion
            $uploadedFilesToDelete = $this->getUploadedFiles($where);
        }
        //delete record
        $this->query
            ->table($this->table());
        $this->query->buildWhere($where);
        $this->query->delete();
        //uploads
        if($this->hasUploads()) {
            $uploadKeys = $this->getUploadKeys();
            //group files by upload key
            $uploadedFilesByUploadKey = [];
            foreach ($uploadKeys as $uploadKey) {
                $uploadedFilesByUploadKey[$uploadKey] = [];
            }
            foreach ($uploadedFilesToDelete as $uploadedFileToDelete) {
                $uploadedFilesByUploadKey[$uploadedFileToDelete->upload_key][] = $uploadedFileToDelete;
            }
            foreach ($uploadedFilesByUploadKey as $uploadKey => $uploadedFilesToDelete) {
                $this->unlinkUploadedFiles($uploadKey, $uploadedFilesToDelete);
            }
        }
    }
    
    /**********
    * LOCALES *
    **********/
    
    /**
    * Saves locales values
    * @param mixed $primaryKeyValue
    * @param array $localesValues: indexes are localized fields names, values are array indexed by language code with localized values
    */
    public function saveLocales($primaryKeyValue, $localesValues)
    {
        //create uploads table if necessary
        $localesTableName = sprintf('%s_locales', $this->table());
        if (!$this->query->tableExists($localesTableName)) {
            throw new \Exception(sprintf('missing %s locales tables for model %s', $localesTableName, getInstanceNamespace($this)));
            
        }
        //reset values
        $this->query
            ->table($localesTableName)
            ->where($this->config->primaryKey, $primaryKeyValue)
            ->delete();
        //loop fields
        $records = [];
        foreach ($localesValues as $languageCode => $fieldLocalesValues) {
            $record = [
                'language_code' => $languageCode,
                $this->config->primaryKey => $primaryKeyValue
            ];
            //loop languages
            foreach ($fieldLocalesValues as $fieldName => $fieldValue) {
                $record[$fieldName] = $fieldValue;
            }
            $records[] = $record;
        }
        $this->query
            ->table($localesTableName)
            ->insert($records);
    }
    
    /**********
    * UPLOADS *
    **********/
    
    /**
    * Gets the uploads folder
    */
    public function getUploadsFolder(): string
    {
        return str_replace('private/', 'public/', getInstancePath($this));
    }
    
    /**
    * Gets an upload folder
    * @param string $uploadKey
    */
    public function getUploadFolder(string $uploadKey): string
    {
        return sprintf('%s/%s', $this->getUploadsFolder(), $uploadKey);
    }
    
    /**
    * Gets an output folder
    * @param string $uploadKey
    * @param string $outputKey
    */
    public function getOutputFolder(string $uploadKey, string $outputKey): string
    {
        return sprintf('%s/%s', $this->getUploadFolder($uploadKey), $outputKey);
    }
    
    /**
    * Gets an uploaded file absolute path for an output
    * @param string $uploadKey
    * @param string $outputKey
    * @param string $fileName
    */
    public function getOutputFilePath(string $uploadKey, string $outputKey, string $fileName): string
    {
        return sprintf('%s/%s/%s', $this->getUploadFolder($uploadKey), $outputKey, $fileName);
        return str_replace(ABS_PATH_TO_ROOT, '', $absolutePath);
        
    }
    
    /**
    * Gets an uploaded file path to be used into templates
    * @param string $uploadKey
    * @param string $outputKey
    * @param string $fileName
    */
    public function getPublicOutputFilePath(string $uploadKey, string $outputKey, string $fileName): string
    {
        $absolutePath = $this->getOutputFilePath($uploadKey, $outputKey, $fileName);
        return str_replace(ABS_PATH_TO_ROOT, '', $absolutePath);
        
    }
    
    /**
    * Gets the uploads table name
    */
    protected function uploadTable(): string
    {
        return sprintf('%s_uploads', $this->table());
    }
    
    /**
    * Gets uploads records
    * @param array $where: array of arrays, each with 2 elements (field name and value, operator defaults to '=') or 3 elements (field name, operator, value)
    */
    protected function getUploadedFiles($where): array
    {
        $this->query
            ->table($this->uploadTable());
        //where conditions
        if(!empty($where)) {
            $this->query->buildWhere($where);
        }
        return $this->query->get();
    }
    
    /**
    * Gets record uploaded files names
    * @param mixed $primaryKeyValue: value of record primary key field
    * @param string $uploadKey
    */
    protected function getUploadedFilesNames($primaryKeyValue, string $uploadKey = null): array
    {
        $this->query
            ->table($this->uploadTable())
            ->where($this->config->primaryKey, $primaryKeyValue);
        if($uploadKey) {
            $this->query->where('upload_key', $uploadKey);
        }
        $uploadedFiles = $this->query->get();
        //extract uploaded files names
        $uploadedFilesNames = array_map(
            function($record) {
                return $record->file_name;
            },
            $uploadedFiles
        );
        return $uploadedFilesNames;
    }
    
    /**
    * Delete uploads records
    * @param mixed $primaryKeyValue: value of record primary key field
    * @param mixed $uploadKey
    */
    protected function deleteUploadedFiles($primaryKeyValue, string $uploadKey = null)
    {
        $this->query
            ->table($this->uploadTable())
            ->where($this->config->primaryKey, $primaryKeyValue);
        if($uploadKey) {
            $this->query->where('upload_key', $uploadKey);
        }
        $this->query->delete();
    }
    
    /**
    * Creates the uploads table
    */
    public function createUploadsTable()
    {
        $uploadTableName = $this->uploadTable();
        $uploadTablePrimaryKeyField = sprintf('%s_id', $uploadTableName);
        $modelTableFK = sprintf('%s_ibfk_1', $uploadTableName);
        $sql = <<<EOT
        CREATE TABLE `$uploadTableName` (
          `$uploadTablePrimaryKeyField` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `{$this->config->primaryKey}` int(10) unsigned NOT NULL,
          `upload_key` varchar(64) NOT NULL,
          `file_name` varchar(64) NOT NULL,
          PRIMARY KEY (`$uploadTablePrimaryKeyField`),
          KEY `{$this->config->primaryKey}` (`{$this->config->primaryKey}`),
          CONSTRAINT `$modelTableFK` FOREIGN KEY (`{$this->config->primaryKey}`) REFERENCES `{$this->table()}` (`{$this->config->primaryKey}`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
EOT;
        $this->query->query($sql);
    }
    
    /**
    * Saves uploads values
    * @param mixed $primaryKeyValue
    * @param object $uploadsValues: indexes are uploads keys, values are strings with images names separated by |
    */
    public function saveUploadsFiles($primaryKeyValue, object $uploadsValues)
    {
        //create uploads table if necessary
        $uploadTableName = sprintf('%s_uploads', $this->table());
        if (!$this->query->tableExists($uploadTableName)) {
            $this->createUploadsTable();
        }
        //loop uploads
        foreach ($this->getUploadKeys() as $uploadKey) {
            if($uploadsValues->$uploadKey) {
                $filesList = explode('|', $uploadsValues->$uploadKey);
                $this->saveUploadFiles($primaryKeyValue, $uploadKey, $filesList);
            }
        }
    }
    
    /**
    * Saves uploads values
    * @param mixed $primaryKeyValue
    * @param string $uploadKey
    * @param array $filesList: array of file names
    */
    protected function saveUploadFiles($primaryKeyValue, string $uploadKey, array $filesList = null)
    {
        //get upload files and candidate them for deletion
        $uploadedFilesToDelete = $this->getUploadedFiles([
            [$this->config->primaryKey, $primaryKeyValue],
            ['upload_key', $uploadKey]
        ]);
        //reset upload files
        $this->deleteUploadedFiles($primaryKeyValue, $uploadKey);
        foreach((array) $filesList as $fileName) {
            //look for file into record uploaded files
            if (($uploadedIndex = array_search($fileName, $uploadedFilesToDelete)) !== false) {
                //remove this file from the ones to be deleted
                unset($uploadedFilesToDelete[$uploadedIndex]);
            }
            //save record
            $record = [
                $this->config->primaryKey => $primaryKeyValue,
                'upload_key' => $uploadKey,
                'file_name' => $fileName
            ];
            $this->query
                ->table($this->uploadTable())
                ->where($this->config->primaryKey, $primaryKeyValue)
                ->where('upload_key', $uploadKey)
                ->insert($record);
        }
        //handle files no longer needed by this upload key deletion
        $this->unlinkUploadedFiles($uploadKey, $uploadedFilesToDelete);
    }
    
    /**
    * Saves uploads values
    * @param string $uploadKey
    * @param array $uploadedFilesToDelete
    */
    protected function unlinkUploadedFiles(string $uploadKey, array $uploadedFilesToDelete)
    {
        //loop files
        foreach ($uploadedFilesToDelete as $uploadedFileToDelete) {
            //check if the file is used by some other record
            $isFileInUse = $this->getUploadedFiles([
                ['upload_key', $uploadKey],
                ['file_name', $uploadedFileToDelete->file_name]
            ]);
            if(!$isFileInUse) {
                //loop outputs
                foreach ($this->getUploadKeyOutputs($uploadKey) as $outputKey) {
                    $outputFilePath = $this->getOutputFilePath($uploadKey, $outputKey, $uploadedFileToDelete->file_name);
                    unlink($outputFilePath);
                }
            }
        }
    }
}
