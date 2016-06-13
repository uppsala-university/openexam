<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportStudentExcel2003Xml.php
// Created: 2015-04-15 00:25:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Students;

use PHPExcel_Reader_Excel2003XML;

/**
 * Import students from Excel 2003 XML file.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportStudentsExcel2003Xml extends ImportStudents
{

        private static $_mimedef = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

        public function __construct($accept = "")
        {
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_reader = new PHPExcel_Reader_Excel2003XML();
                $this->_reader->setReadDataOnly(true);
        }

}
