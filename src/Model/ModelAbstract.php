<?php
declare(strict_types=1);

namespace Simplex\Model;

use Simplex\PixieExtended;
use function Simplex\getInstanceNamespace;
use function Simplex\getInstancePath;

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
    * Returns the view or at least table defined
    */
    public function view(): string
    {
        return $this->config->view ?? $this->config->table;
    }
    
    /**
    * Returns whether the model has at least one upload
    */
    public function hasUploads(): bool
    {
        return isset($this->config->uploads);
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
                $errorType = 'duplicate_entry';
                if(preg_match('/Duplicate entry/', $errorMessage) === 1) {
                    //extract field name
                    preg_match("/'([0-9a-zA-Z_]+)'$/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
                //failed foreign key constraint
                $errorType = 'fk_constraint';
                if(preg_match('/a foreign key constraint fails/', $errorMessage) === 1) {
                    //extract field name
                    preg_match("/FOREIGN KEY \(`([0-9a-zA-Z_]+)`\)/", $errorMessage, $matches);
                    $data = [$matches[1]];
                }
            break;
        }
        return (object) [
            'code' => sprintf('SQLSTATE_%s_%s', $errorCode, $errorType),
            'data' => $data
        ];
    }
    
    /*********
    * SELECT *
    *********/

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
    public function first(array $where = []): object
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
        return $this->query
            ->table($this->table())
            ->insert($fieldsValues);
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
    * Deletes a record
    * @param mixed $primaryKeyValue
    */
    public function delete($primaryKeyValue)
    {
        $this->query
            ->table($this->table())
            ->where($this->config->primaryKey, $primaryKeyValue)
            ->delete();
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
    * Gets the uploads table name
    */
    protected function uploadTable(): string
    {
        return sprintf('%s_uploads', $this->table());
    }
    
    /**
    * Gets record uploads
    * @param mixed $primaryKeyValue: value of recrod primary key field
    */
    protected function getUploadedFiles($primaryKeyValue): array
    {
        $this->query
            ->table($this->uploadTable())
            ->where($this->config->primaryKey, $primaryKeyValue);
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
    * @param array $uploadsValues: indexes are uploads keys, values are json string with the fields informations
    */
    public function saveUploadsFiles($primaryKeyValue, $uploadsValues)
    {
        //create uploads table if necessary
        $uploadTableName = sprintf('%s_uploads', $this->table());
        if (!$this->query->tableExists($uploadTableName)) {
            $this->createUploadsTable();
        }
        //loop uploads
        foreach (array_keys($this->config->uploads) as $uploadKey) {
            $filesList = json_decode($uploadsValues->$uploadKey);
            $this->saveUploadFiles($uploadKey, $filesList);
        }
    }
    /**
    * Saves uploads values
    * @param mixed $primaryKeyValue
    * @param array $filesList
    */
    public function saveUploadFiles($uploadKey, $filesList)
    {
        //get record uploaded files and candidate them for deletion
        $uploadedFilesToDelete = $this->getUploadedFiles($primaryKeyValue, $uploadKey);
        foreach((array) $filesList as $fileObject) {
            $fileName = $fileObject->name;
            //look for file into record uploaded files
            if (($uploadedIndex = array_search($fileName, $uploadedFilesToDelete)) !== false) {
                //remove this file from the ones to be deleted
                unset($uploadedFilesToDelete[$uploadedIndex]);
            }
        }
        //remove files no longer needed
        $this->unlinkUploadedFiles($uploadedFilesToDelete);
    }
}
