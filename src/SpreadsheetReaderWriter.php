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
    * @param string $type: csv | xlsx, if null it is guessed by file itself
    * @param bool $firstRowIsHEaders
    * @param bool $rowsToObjects: turn rows arrays into object, it works only if $firstRowIsHEaders is true (otherwise thera are no properties to be used for the object)
    */
    public function read(string $path, string $type = null, bool $firstRowIsHEaders = true, bool $rowsToObjects = false)
    {
        switch ($type) {
            case 'csv':
            $this->reader = new PhpSpreadsheet\Reader\Csv();
            break;
            case 'xlsx':
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
            //rows to object
            if($rowsToObjects) {
              $objectsRows = [];
              foreach ((array) $rows as $row) {
                $rowObject = new \stdClass;
                foreach ($headers as $j => $header) {
                  //skip empty headers columns
                  if(!trim($header)) {
                    continue;
                  }
                  $rowObject->$header = $row[$j];
                }
                $objectsRows[] = $rowObject;
              }
              $rows = $objectsRows;
            }
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
    * @param string $type: csv | xlsx |
    * @param string $output: f | file | b | browser
    * @param string $FileName: in case of output = file must be complete path
    * @param array $rows: an array of objects (like a recordset)
    * @param array $headersRow
    * @param string $delimiter delimiter for csv
    */
    public function write(string $type, string $output, string $fileName, array $rows, array $headersRow = [], string $delimiter = '')
    {
      //AdvancedValuebinder.php automatically turns on "wrap text" for the cell when it sees a newline character in a string that you are inserting in a cell
      \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder() );
      //create workbook and sheet
      $spreadsheet = new PhpSpreadsheet\Spreadsheet();
      $sheet = $spreadsheet->setActiveSheetIndex(0);
      //set starting sheet row index
      $y = 1;
      $headerRow = 0;
      //headers row
      if(!empty($headersRow)) {
        $numCols = count($headersRow);
        for($x = 1;$x <= $numCols; $x++) {
          $sheet->setCellValueByColumnAndRow($x, $y, $headersRow[$x - 1]);
        }
        $headerRow = 1;
        $y++;
      }
      //loop rows
      $numRows = count($rows);
      $numCols = $numCols ?? ($numRows ? count(get_object_vars(($rows[0]))) : 0);
      for($i = 0;$i < $numRows; $i++) {
        $row = $rows[$i];
        $y = $i + $headerRow + 1;
        //loop cells
        $x = 1;
        foreach($row as $value) {
          $sheet->setCellValueByColumnAndRow($x, $y, $value);
          //$sheet->getStyle([$x,$y])->getAlignment()->setWrapText(true);
          $x++;
        }
      }
      switch ($type) {
        case 'csv':
          $this->writer = new PhpSpreadsheet\Writer\Csv($spreadsheet);
          $contentType = 'text/csv';
        break;
        case 'xlsx':
          $this->writer = new PhpSpreadsheet\Writer\Xlsx($spreadsheet);
          $contentType = 'application/vnd.ms-excel';
        break;
      }
      //output
      switch ($output) {
        case 'f':
        case 'file':
          $this->writer->save($fileName);
        break;
        case 'b':
        case 'browser':
          header(sprintf('Content-Type: %s', $contentType));
          header(sprintf('Content-Disposition: attachment;filename="%s"', $fileName));
          header('Cache-Control: max-age=0');
          /*// If you're serving to IE 9, then the following may be needed
          header('Cache-Control: max-age=1');
          // If you're serving to IE over SSL, then the following may be needed
          header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
          header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
          header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
          header('Pragma: public'); // HTTP/1.0*/

          $this->writer->save('php://output');
        break;
      }
    }
}
