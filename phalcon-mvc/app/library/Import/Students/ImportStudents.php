<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudents.php
// Created: 2015-04-15 00:21:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use OpenExam\Library\Import\ImportBase;
use OpenExam\Library\Import\ImportData;
use PHPExcel;
use PHPExcel_Reader_IReader;
use PHPExcel_Worksheet;

/**
 * Base class for student import classes.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ImportStudents extends ImportBase
{

        const XMLDOC = '<openexam/>';
        // Constant for setMapping():
        const TAG = 'tag';
        const USER = 'user';
        const CODE = 'code';
        const PNR = 'pnr';
        const ROW = 'row';

        /**
         * The file reader.
         * @var PHPExcel_Reader_IReader
         */
        protected $_reader;
        /**
         * The excel object.
         * @var PHPExcel 
         */
        private $_excel;
        /**
         * The active sheet.
         * @var PHPExcel_Worksheet 
         */
        private $_sheet;
        /**
         * The student data.
         * @var StudentData 
         */
        private $_sdat;

        public function __construct($accept)
        {
                parent::__construct($accept);
                $this->_data = new ImportData(self::XMLDOC);
        }

        public function read()
        {
                // 
                // Load file and open first sheet:
                // 
                $this->_excel = $this->_reader->load($this->_file);
                $this->_sheet = $this->_excel->setActiveSheetIndex(0);

                // 
                // Set column, row and data from sheet:
                // 
                $cols = ord($this->_sheet->getHighestColumn()) - ord('A');
                $rows = $this->_sheet->getHighestRow();
                $data = $this->_sheet->toArray();

                // 
                // Process student data:
                // 
                $this->_sdat = new StudentData($data, $rows, $cols);

                // 
                // Bug out on empty data:
                // 
                if ($this->_sdat->isMissing()) {
                        throw new ImportException("No data detected on first sheet", Error::PRECONDITION_FAILED);
                }
        }

        /**
         * Get raw sheet data.
         * @return array
         */
        public function getSheet()
        {
                return $this->_sdat->data;
        }

        /**
         * Get number of rows.
         * @return int
         */
        public function getRows()
        {
                return $this->_sdat->rows;
        }

        /**
         * Get number of columns.
         * @return int
         */
        public function getColumns()
        {
                return $this->_sdat->cols;
        }

}

/**
 * Student data helper class.
 * 
 * @property-read array $data The data array.
 * @property-read int $rows The number of rows.
 * @property-read int $cols The number of columns.
 */
class StudentData
{

        /**
         * The number of columns.
         * @var int 
         */
        private $_cols;
        /**
         * The number of rows.
         * @var int 
         */
        private $_rows;
        /**
         * Sheet data read (trimmed).
         * @var array 
         */
        private $_data;
        /**
         * The defined rows and columns.
         * @var array 
         */
        private $_defined = array(
                'rows' => array(),
                'cols' => array()
        );

        /**
         * Constructor.
         * 
         * @param array $data The sheet data.
         * @param int $rows The number of rows.
         * @param int $cols The number of columns.
         */
        public function __construct($data, $rows, $cols)
        {
                $this->_data = $data;
                $this->_rows = $rows;
                $this->_cols = $cols;

                $this->process();
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'data':
                                return $this->_data;
                        case 'rows':
                                return $this->_rows;
                        case 'cols':
                                return $this->_cols;
                }
        }

        /**
         * Check if data exist.
         * @return bool
         */
        public function isMissing()
        {
                return count($this->_data) == 0;
        }

        /**
         * Process input data.
         */
        private function process()
        {
                $this->prepare();
                $this->detect();
                $this->cleanup();

                $this->remap();
        }

        /**
         * Prepare data processing.
         */
        private function prepare()
        {
                for ($i = 0; $i < $this->_rows; ++$i) {
                        $this->_defined['rows'][$i] = false;
                }
                for ($i = 0; $i < $this->_rows; ++$i) {
                        $this->_defined['cols'][$i] = false;
                }
        }

        /**
         * Detect empty cells.
         */
        private function detect()
        {
                for ($r = 0; $r < $this->_rows; ++$r) {
                        for ($c = 0; $c <= $this->_cols; ++$c) {
                                if (strlen($this->_data[$r][$c]) != 0) {
                                        $this->_defined['rows'][$r] = true;
                                        $this->_defined['cols'][$c] = true;
                                }
                        }
                }
        }

        /**
         * Compact data.
         */
        private function cleanup()
        {
                for ($i = 0; $i < $this->_rows; ++$i) {
                        if (!$this->_defined['rows'][$i]) {
                                $this->removeRow($i);
                        }
                }
                for ($i = 0; $i <= $this->_cols; ++$i) {
                        if (!$this->_defined['cols'][$i]) {
                                $this->removeColumn($i);
                        }
                }
        }

        /**
         * Remap array indexes.
         */
        private function remap()
        {
                if (array_walk($this->_data, function(&$entry, $key) {
                            $this->_data[$key] = array_values($entry);
                    })) {
                        $this->_data = array_values($this->_data);
                }

                $this->_rows = count($this->_data);
                $this->_cols = count($this->_data[0]);
        }

        /**
         * Remove column from sheet data.
         * @param int $column The column index.
         */
        private function removeColumn($column)
        {
                for ($i = 0; $i < $this->_rows; ++$i) {
                        unset($this->_data[$i][$column]);
                }
        }

        /**
         * Remove row from sheet data.
         * @param int $row The row index.
         */
        private function removeRow($row)
        {
                unset($this->_data[$row]);
        }

}
