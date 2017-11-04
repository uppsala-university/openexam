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
                $this->_cols = ord($this->_sheet->getHighestColumn()) - ord('A');
                $this->_rows = $this->_sheet->getHighestRow();
                $this->_sdat = $this->_sheet->toArray();

                // 
                // Trim read data:
                // 
                $this->removeEmptyCells();

                // 
                // Bug out on empty data:
                // 
                if (count($this->_sdat) == 0) {
                        throw new ImportException("No data detected on first sheet", Error::PRECONDITION_FAILED);
                }

                // 
                // Update row and column count:
                // 
                $this->_rows = count($this->_sdat);
                $this->_cols = count($this->_sdat[0]);
        }

        /**
         * Get raw sheet data.
         * @return array
         */
        public function getSheet()
        {
                return $this->_sdat;
        }

        /**
         * Get number of rows.
         * @return int
         */
        public function getRows()
        {
                return $this->_rows;
        }

        /**
         * Get number of columns.
         * @return int
         */
        public function getColumns()
        {
                return $this->_cols;
        }

        /**
         * Cleanup empty rows and columns.
         */
        private function removeEmptyCells()
        {
                $defined = array(
                        'rows' => array(),
                        'cols' => array()
                );

                for ($i = 0; $i < $this->_rows; ++$i) {
                        $defined['rows'][$i] = false;
                }
                for ($i = 0; $i < $this->_rows; ++$i) {
                        $defined['cols'][$i] = false;
                }

                for ($r = 0; $r < $this->_rows; ++$r) {
                        for ($c = 0; $c <= $this->_cols; ++$c) {
                                if (strlen($this->_sdat[$r][$c]) != 0) {
                                        $defined['rows'][$r] = true;
                                        $defined['cols'][$c] = true;
                                }
                        }
                }

                for ($i = 0; $i < $this->_rows; ++$i) {
                        if (!$defined['rows'][$i]) {
                                $this->removeRow($i);
                        }
                }
                for ($i = 0; $i <= $this->_cols; ++$i) {
                        if (!$defined['cols'][$i]) {
                                $this->removeColumn($i);
                        }
                }

                $this->remapIndexes();
        }

        /**
         * Remove column from sheet data.
         * @param int $column The column index.
         */
        private function removeColumn($column)
        {
                for ($i = 0; $i < $this->_rows; ++$i) {
                        unset($this->_sdat[$i][$column]);
                }
        }

        /**
         * Remove row from sheet data.
         * @param int $row The row index.
         */
        private function removeRow($row)
        {
                unset($this->_sdat[$row]);
        }

        /**
         * Remap array indexes.
         */
        private function remapIndexes()
        {
                if (array_walk($this->_sdat, function(&$entry, $key) {
                            $this->_sdat[$key] = array_values($entry);
                    })) {
                        $this->_sdat = array_values($this->_sdat);
                }
        }

}
