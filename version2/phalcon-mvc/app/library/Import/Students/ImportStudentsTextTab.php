<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsTextTab.php
// Created: 2015-04-15 00:33:07
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_CSV;

/**
 * Import students from TAB-separated values file.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsTextTab extends ImportStudents
{

        private static $_mimedef = array("text/tab-separated-values", "text/plain");

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new PHPExcel_Reader_CSV();
                $this->_reader->setDelimiter("\t");
                $this->_reader->setReadDataOnly(true);
        }

}
