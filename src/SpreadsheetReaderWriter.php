<?php
declare(strict_types=1);

namespace Simplex;

//use \Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use \PhpOffice\PhpSpreadsheet;
//use \Box\Spout\Writer\Common\Creator\WriterEntityFactory;
//use \Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
//use \Box\Spout\Common\Entity\Style\Style;

/*
* Uses Box\Spout (http://opensource.box.com/spout) to read and write spreadsheets (csv, ods and xlsx)
*/
class SpreadsheetReaderWriter
{    
    /*
    * File type instance
    * @param string csv | ods | xlsx
    */
    private $type;
    
    /*
    * Writer instance
    * @param Box\Spout\Writer\XLSX\Writer
    */
    private $reader;
    
    /*
    * Writer instance
    * @param Box\Spout\Writer\XLSX\Writer
    */
    private $writer;
    
    /*
    * Reads a spreadsheet
    * @param string $path
    * @param string $type: csv | xslx
    * @param bool $firstRowIsHEaders
    * @param bool $rowsToObjects
    */
    public function read(string $path, $type = null, $firstRowIsHEaders = true, $rowsToObjects = false)
    {
        switch ($type) {
            case 'csv':
            $this->reader = new PhpSpreadsheet\Reader\Csv();
            break;
            case 'xslx':
              //read anly data and ignore styiling
              $this->reader = new PhpSpreadsheet\Reader\Xlsx();
            break;
            default:
                $this->reader = PhpSpreadsheet\IOFactory::createReaderForFile($path);
            break;
        }
        $this->reader->setReadDataOnly(true);
        $this->reader->setLoadAllSheets();
        $spreadsheet = $this->reader->load($path);
        $loadedSheetNames = $spreadsheet->getSheetNames();
        $sheets = [];
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
          /*
          * PhpOffice\PhpSpreadsheet\Worksheet::toArray parameters:
          * @param mixed $nullValue Value returned in the array entry if a cell doesn't exist
          * @param bool $calculateFormulas Should formulas be calculated?
          * @param bool $formatData Should formatting be applied to cell values?
          * @param bool $returnCellRef False - Return a simple array of rows and columns indexed by number counting from zero
          *                               True - Return rows and columns indexed by their actual row and column IDs
          */
          $rows = $spreadsheet->getSheet($sheetIndex)->toArray(null, true, true, false);
          //headers row
          if($firstRowIsHEaders) {
            $headers = array_shift($rows);
          }
          //rows to object
          if($rowsToObjects) {
            $objectsRows = [];
            foreach ((array) $rows as $row) {
              $rowObject = new \stdClass;
              foreach ($headers as $j => $header) {
                //in case of empty cells at the and of the header row
                if(!isset($row[$j])) {
                  continue(2);
                } else {
                  $rowObject->$header = $row[$j];
                }
              }
              $objectsRows[] = $rowObject;
            }
            $rows = $objectsRows;
          }
          $sheets[] = $rows;
        }
        return $sheets;
    }
    
    /*
    * Creates a row from an array
    * @param array $row
    * @param Box\Spout\Writer\Common\Creator\Style\Style $style
    */
    private function addRowFromArray(array $row = [], Style $style = null)
    {
        if(!empty($row)) {
            array_walk(
                $row,
                function(&$value) {
                    $value = WriterEntityFactory::createCell($value);
                }
            );
            $this->writer->addRow(WriterEntityFactory::createRow($row, $style));
        }
    }
    
    /*
    * Creates a row from an object
    * @param object $row
    * @param Box\Spout\Writer\Common\Creator\Style\Style $style
    */
    private function addRowFromObject(object $row = null, Style $style = null)
    {
        if($row) {
            array_walk(
                $row,
                function(&$value) {
                    $value = WriterEntityFactory::createCell($value);
                }
            );
            $this->writer->addRow(WriterEntityFactory::createRow((array) $row, $style));
        }
    }
    
    /*
    * Adds headers row
    * @param array $row
    */
    private function addHeadersRow(array $row = [])
    {
        switch ($this->type) {
            case 'ods':
            case 'xlsx':
            $style = (new StyleBuilder())
               ->setFontBold()
               ->build();
            break;
            default:
                $style = null;
            break;
        }
        $this->addRowFromArray($row, $style);
    }
    
    /*
    * Writes a spreadsheet
    * @param string $type: c | csv | o | ods | x | xlsx
    * @param string $output: f | file | b | browser
    * @param string $FileName: in case of output = file must be complete path
    * @param array $rows: an array of objects (like a recordset)
    * @param array $headersRow
    * @param string $delimiter delimiter for csv
    */
    public function write(string $type, string $output, string $fileName, array $rows, array $headersRow = [], string $delimiter = '')
    {
        //normalize parameters short values
        switch ($type) {
            case 'c':
                $type = 'csv';
            break;
            case 'o':
                $type = 'ods';
            break;
            case 'x':
                $type = 'xlsx';
            break;
        }
        $this->type = $type;
        switch ($output) {
            case 'f':
                $output = 'file';
            break;
            case 'b':
                $output = 'browser';
            break;
        }
        //create writer
        $method = sprintf('create%sWriter', strtoupper($this->type));
        $this->writer = WriterEntityFactory::$method();
        //delimiter
        if($delimiter && $type == 'csv') {
          $this->writer->setFieldDelimiter($delimiter);
        }
        //output
        switch ($output) {
            case 'file':
                //save to filesystem
                $this->writer->openToFile($fileName);
            break;
            case 'browser':
                //to browser
                $this->writer->openToBrowser($fileName);
            break;
        }
        //headers row
        $this->addHeadersRow($headersRow);
        //rows
        foreach ($rows as $row) {
            $this->addRowFromObject($row);
        }
        //close
        $this->writer->close();
    }
}
