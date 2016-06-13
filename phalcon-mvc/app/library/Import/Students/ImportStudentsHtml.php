<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentsHtml.php
// Created: 2015-04-15 23:07:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_HTML;

/**
 * Import students from HTML document.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsHtml extends ImportStudents
{

        private static $_mimedef = "text/html";

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new PHPExcel_Reader_HTML();
                $this->_reader->setReadDataOnly(true);
        }

}
