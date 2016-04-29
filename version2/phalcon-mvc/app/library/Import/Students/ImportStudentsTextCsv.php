<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsTextCsv.php
// Created: 2015-04-15 00:31:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_CSV;

/**
 * Import students from CSV (comma separated values) file.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsTextCsv extends ImportStudents
{

        private static $_mimedef = "text/csv";

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new PHPExcel_Reader_CSV();
                $this->_reader->setReadDataOnly(true);
        }

}
