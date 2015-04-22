<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportData.php
// Created: 2015-04-14 23:49:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import;

use SimpleXMLElement;
use stdClass;

/**
 * The import data.
 * 
 * All import classes should use this class to define the data to be inserted
 * in the database. For creating an instance of this class and load an
 * XML document, do either one of these:
 * <code>
 * // Load from file:
 * $data = simplexml_load_file($xmlfile, 'ImportData');
 * 
 * // Load from string:
 * $data = simplexml_load_string($xmldoc, 'ImportData');
 * 
 * // Pass string direct to base class constructor:
 * $data = new ImportData('&lt;root/&gt;');
 * </code>
 * 
 * This really shows how sucky OOP in PHP is. It's not possible to define the
 * map as property as it interfers with SimpleXMLElement.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see schemas/project/openexam-v1-6073.xsd
 */
class ImportData extends SimpleXMLElement
{

        /**
         * @var stdClass
         */
        private static $mmap;   // Must be static

        /**
         * Get mapping object.
         * @return stdClass
         */
        public function map()
        {
                if (!isset(self::$mmap)) {
                        self::$mmap = new stdClass();
                }
                return self::$mmap;
        }

}
