<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportPingPongExcel.php
// Created: 2015-04-15 00:15:06
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Questions;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use stdClass;

/**
 * Questions import from PING-PONG.
 * 
 * This class supports import of question bank from PING-PONG in Excel
 * file format.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportPingPongExcel extends ImportPingPong
{

        private static $_mimedef = array("application/vnd.ms-excel", "application/vnd.ms-office");
        private $_reader;

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new Spreadsheet_Excel_Reader();
        }

        public function read()
        {
                $this->_reader->read($this->_file);

                $sheet = new stdClass();
                $sheet->rows = $this->_reader->sheets[0]['numRows'];
                $sheet->cols = $this->_reader->sheets[0]['numCols'];
                $sheet->cell = $this->_reader->sheets[0]['cells'];

                if ($sheet->cell[1][1] != self::EXPECT) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::EXPECT);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }
                if ($sheet->cell[2][2] != self::FORMAT) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::FORMAT);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }

                for ($i = 1; $i <= $sheet->rows; $i++) {
                        if (!isset($sheet->cell[$i])) {
                                continue;
                        } elseif (!isset($sheet->cell[$i][2])) {
                                $sheet->cell[$i][2] = "";
                        } else {
                                parent::append($sheet->cell[$i][1], $sheet->cell[$i][2]);
                        }
                }

                parent::read();
        }

}
