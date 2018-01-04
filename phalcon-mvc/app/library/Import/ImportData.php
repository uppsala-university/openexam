<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
        private static $_mmap;   // Must be static

        /**
         * Get mapping object.
         * @return stdClass
         */
        public function map()
        {
                if (!isset(self::$_mmap)) {
                        self::$_mmap = new stdClass();
                }
                return self::$_mmap;
        }

}
