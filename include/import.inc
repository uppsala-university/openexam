<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/import.inc
// Author: Anders Lövgren
// Date:   2010-12-09
//
// Import utility.
//

class ImportException extends Exception
{

        public function __construct($message, $code = 0, $previous = null)
        {
                parent::__construct($message, $code, $previous);
        }

}

//
// Include PHP-ExcelReader files:
//
require_once('include/import/excel/reader.php');

//
// The interface for concrete import classes. Currently we are only using
// this interface for importing question data to an exam.
//
interface Import
{

        //
        // Open file and prepare to read it.
        //
        function open($file);

        //
        // Parse current opened import file and save data to exam.
        //
        function read($exam);

        //
        // Close opened import file.
        //
        function close();

        //
        // Factory function returning an importer:
        //
        public static function getReader($file)
        {
                $extension = strrchr($file, ".");

                if ($extension == "xls") {
                        return new ExcelImport();
                } else {
                        throw new ImportException(sprintf(_("Don't know how import *.%s files"), $extension));
                }
        }

}

class ExcelImport implements Import
{

        private $data;

        public function open($file)
        {
                $this->data = new Spreadsheet_Excel_Reader();
                $this->data->setOutputEncoding('CP1251');
        }

        public function read($exam)
        {
                for ($i = 1; $i <= $this->data->sheets[0]['numRows']; $i++) {
                        for ($j = 1; $j <= $this->data->sheets[0]['numCols']; $j++) {
                                echo "\"" . $this->data->sheets[0]['cells'][$i][$j] . "\",";
                        }
                        echo "\n";
                }
        }

        public function close()
        {
                $this->data = null;
        }

}

?>
