<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsGnumeric.php
// Created: 2015-04-15 00:29:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_Gnumeric;

/**
 * Import students from Gnumeric spreadsheet.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsGnumeric extends ImportStudents
{

        private static $_mimedef = "application/x-gnumeric";

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new PHPExcel_Reader_Gnumeric();
                $this->_reader->setReadDataOnly(true);
        }

}
