<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsSylk.php
// Created: 2015-04-15 23:09:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_SYLK;

/**
 * Import students from spreadsheet interchange (SYLK) document.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsSylk extends ImportStudents
{

        private static $mimedef = "text/spreadsheet";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_SYLK();
                $this->reader->setReadDataOnly(true);
        }
}
