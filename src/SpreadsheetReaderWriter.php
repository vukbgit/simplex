<?php
declare(strict_types=1);

namespace Simplex;

use \Box\Spout\Writer\Common\Creator\ReaderEntityFactory;
use \Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use \Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use \Box\Spout\Common\Entity\Style\Style;

/*
* Uses Box\Spout (http://opensource.box.com/spout) to0 read and write spreadsheets (csv, ods and xlsx)
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
    private $writer;
    
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
    */
    public function write(string $type, string $output, string $fileName, array $rows, array $headersRow = [])
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
