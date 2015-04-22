<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        private static $supported = array(6071, 6072, 6073);
        /**
         * Supported MIME types.
         * @var string[] 
         */
        private static $mimedef = array("application/xml", "text/xml");

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
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->data = simplexml_load_file($this->file, 'ImportData');
                $this->verify();
        }

        /**
         * Verify that loaded document can be imported.
         * @throws ImportException
         */
        private function verify()
        {
                if (!isset($this->data['format'])) {
                        $message = _("Missing XML format attribute. This don't look like an OpenExam project data file.");
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }
                if (!in_array($this->data['format'], self::$supported)) {
                        $message = sprintf(_("Unsupported XML format version: %d. This file can not be imported."), $this->data['@attributes']['format']);
                        throw new ImportException($message, Error::NOT_ACCEPTABLE);
                }
        }

}
