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
// File:    ImportOpenExam.php
// Created: 2015-04-15 00:07:40
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import\Project;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use OpenExam\Library\Import\ImportBase;
use function _;

/**
 * Import OpenExam project.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportOpenExamXml extends ImportBase
{

        /**
         * Supported format versions.
         * @var int[]
         */
        private static $_supported = array(6071, 6072, 6073);
        /**
         * Supported MIME types.
         * @var string[] 
         */
        private static $_mimedef = array("application/xml", "text/xml");

        /**
         * Constructor.
         * @param string|array $accept Accepted MIME-type(s).
         * @throws ImportException
         */
        public function __construct($accept = "")
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ImportException("The SimpleXML extension is not loaded", Error::SERVICE_UNAVAILABLE);
                }
                if (!extension_loaded("dom")) {
                        throw new ImportException("The DOM Document extension (dom) is not loaded", Error::SERVICE_UNAVAILABLE);
                }
                parent::__construct(self::$_mimedef);
        }

        public function open()
        {
                $this->_data = simplexml_load_file($this->_file, 'ImportData');
                $this->verify();
        }

        /**
         * Verify that loaded document can be imported.
         * @throws ImportException
         */
        private function verify()
        {
                if (!isset($this->_data['format'])) {
                        $message = _("Missing XML format attribute. This don't look like an OpenExam project data file.");
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }
                if (!in_array($this->_data['format'], self::$_supported)) {
                        $message = sprintf(_("Unsupported XML format version: %d. This file can not be imported."), $this->_data['@attributes']['format']);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }
        }

}
