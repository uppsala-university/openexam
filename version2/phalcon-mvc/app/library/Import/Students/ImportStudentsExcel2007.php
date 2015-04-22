<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsExcel2007.php
// Created: 2015-04-15 00:23:42
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_Excel2007;

/**
 * Import students from Excel 2007 file.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsExcel2007 extends ImportStudents
{

        private static $mimedef = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Excel2007();
                $this->reader->setReadDataOnly(true);
        }

}
