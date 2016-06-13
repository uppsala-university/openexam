<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ImportBase.php
// Created: 2015-04-15 00:05:42
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use Phalcon\Http\Request\FileInterface;
use Phalcon\Mvc\User\Component;

/**
 * The base class for all importers.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ImportBase extends Component implements Import
{

        /**
         * The sections to import.
         * @var int
         */
        protected $_filter;
        /**
         * Accepted MIME-types.
         * @var string|array
         */
        protected $_accept;
        /**
         * Name of file.
         * @var string
         */
        protected $_name;
        /**
         * File path
         * @var string 
         */
        protected $_file;
        /**
         * MIME type.
         * @var string 
         */
        protected $_mime;
        /**
         * File size.
         * @var int 
         */
        protected $_size;
        /**
         * The data to import.
         * @var ImportData 
         */
        protected $_data;

        /**
         * Constructor.
         * @param string|array $accept Accepted MIME-types.
         * @param int $filter The sections to import.
         */
        public function __construct($accept, $filter = Import::OPENEXAM_IMPORT_INCLUDE_ALL)
        {
                $this->_accept = $accept;
                $this->_filter = $filter;
        }

        /**
         * Set file to import.
         * 
         * This function performs sanity check on the submitted MIME type
         * and throws an ImportException if the MIME-type don't match the 
         * expected.
         * 
         * @param FileInterface $file The uploaded file.
         * @throws ImportException
         */
        public function setFile($file)
        {
                $this->_file = $file->getTempName();
                $this->_mime = $file->getType();
                $this->_name = $file->getName();
                $this->_size = $file->getSize();

                if (isset($_FILES['file']['error'])) {
                        switch ($_FILES['file']['error']) {
                                case UPLOAD_ERR_INI_SIZE:
                                        throw new ImportException(_("The uploaded file exceeds the upload_max_filesize directive in php.ini."), Error::NOT_ACCEPTABLE);
                                case UPLOAD_ERR_FORM_SIZE:
                                        throw new ImportException(_("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."), Error::NOT_ACCEPTABLE);
                                case UPLOAD_ERR_PARTIAL:
                                        throw new ImportException(_("The uploaded file was only partially uploaded."), Error::NOT_ACCEPTABLE);
                                case UPLOAD_ERR_NO_FILE:
                                        throw new ImportException(_("No file was uploaded."), Error::BAD_REQUEST);
                                case UPLOAD_ERR_NO_TMP_DIR:
                                        throw new ImportException(_("Missing a temporary folder."), Error::INTERNAL_SERVER_ERROR);
                                case UPLOAD_ERR_CANT_WRITE:
                                        throw new ImportException(_("Failed to write file to disk."), Error::INTERNAL_SERVER_ERROR);
                                case UPLOAD_ERR_EXTENSION:
                                        throw new ImportException(_("A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help."), Error::INTERNAL_SERVER_ERROR);
                        }
                }
                if (!is_uploaded_file($this->_file)) {
                        throw new ImportException(_("The file don't reference an uploaded file, possible file attack."), Error::BAD_REQUEST);
                }
                if ($this->_mime == FileImport::OCTET_STREAM) {
                        $this->_mime = FileImport::getMimeType($this->_mime);
                }

                $accepted = false;
                $expected = is_array($this->_accept) ? implode("|", $this->_accept) : $this->_accept;

                if (!isset($this->_mime)) {
                        $accepted = true;       // Give it a try
                } else {
                        if (is_array($this->_accept)) {
                                foreach ($this->_accept as $type) {
                                        if ($type == $this->_mime) {
                                                $accepted = true;
                                                break;
                                        }
                                }
                        } else {
                                if ($this->_mime == $this->_accept) {
                                        $accepted = true;
                                }
                        }
                }

                if (!$accepted) {
                        throw new ImportException(sprintf(_("Wrong MIME type (%s) on uploaded file %s (expected %s)"), $this->_mime, $this->_name, $expected), Error::NOT_ACCEPTABLE);
                }
                if ($this->_size == 0) {
                        throw new ImportException(sprintf(_("Empty file %s uploaded"), $this->_name), Error::BAD_REQUEST);
                }
        }

        /**
         * Set import option filter.
         * @param int $filter Bitmask of zero or more Import::OPENEXAM_IMPORT_INCLUDE_XXX
         */
        public function setFilter($filter)
        {
                $this->_filter = $filter;
        }

        /**
         * Get import option filter.
         * @return int
         */
        public function getFilter()
        {
                return $this->_filter;
        }

        /**
         * Cleanup function.
         * 
         * Remove HTML tags and other junk from the input strings. This function
         * should be called on for any text field read from the excel-file.
         * 
         * @param string $str The input string.
         * @return string
         */
        protected static function cleanup($str)
        {
                $find = array("<br/><br/>", " ?", "<br/>", "  ", "\n\n\n");
                $repl = array("\n\n", "?", " ", " ", "\n\n");

                $str = preg_replace("|<p>(.*?)</p>|", "$1\n\n", $str);
                $str = str_replace($find, $repl, $str);

                return utf8_encode(htmlentities(html_entity_decode(trim($str))));
        }

        public function open()
        {
                // Ignore
        }

        public function read()
        {
                // Ignore
        }

        public function insert($inserter)
        {
                $inserter->insert($this->_data, $this->_filter);
                return $inserter->getExamID();
        }

        public function close()
        {
                // Ignore
        }

        public function getData()
        {
                return $this->_data;
        }

}
