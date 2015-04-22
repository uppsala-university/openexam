<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsOpenDocument.php
// Created: 2015-04-15 00:28:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_OOCalc;

/**
 * Import students from OpenDocument (ODS) spreadsheet.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsOpenDocument extends ImportStudents
{

        private static $mimedef = array(
                "application/vnd.oasis.opendocument.spreadsheet",
                "application/vnd.oasis.opendocument.spreadsheet-template");

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_OOCalc();
                $this->reader->setReadDataOnly(true);
        }

}
