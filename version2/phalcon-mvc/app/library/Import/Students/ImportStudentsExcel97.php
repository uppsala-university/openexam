<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsExcel97.php
// Created: 2015-04-15 00:26:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_Excel5;

/**
 * Import students from Excel 97-2003 (BIFF 5) file.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsExcel97 extends ImportStudents
{

        private static $mimedef = "application/vnd.ms-excel";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Excel5();
                $this->reader->setReadDataOnly(true);
        }

}
