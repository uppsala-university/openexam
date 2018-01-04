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
// File:    FileImport.php
// Created: 2015-04-15 00:34:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use OpenExam\Library\Import\Project\ImportOpenExamXml;
use OpenExam\Library\Import\Questions\ImportPingPongExcel;
use OpenExam\Library\Import\Questions\ImportPingPongText;
use OpenExam\Library\Import\Students\ImportStudentsExcel2003Xml;
use OpenExam\Library\Import\Students\ImportStudentsExcel2007;
use OpenExam\Library\Import\Students\ImportStudentsExcel97;
use OpenExam\Library\Import\Students\ImportStudentsGnumeric;
use OpenExam\Library\Import\Students\ImportStudentsHtml;
use OpenExam\Library\Import\Students\ImportStudentsOpenDocument;
use OpenExam\Library\Import\Students\ImportStudentsSylk;
use OpenExam\Library\Import\Students\ImportStudentsTextCsv;
use OpenExam\Library\Import\Students\ImportStudentsTextTab;
use Phalcon\Mvc\User\Component;
use function _;

/**
 * File upload support class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class FileImport extends Component
{

        /**
         * Unknown MIME type.
         */
        const OCTET_STREAM = "application/octet-stream";
        /**
         * Import file from Ping-Pong.
         */
        const TYPE_PING_PONG = 'pp';
        /**
         * Import OpenExam questions.
         */
        const TYPE_OPEN_EXAM_QUESTIONS = 'oq';
        /**
         * Import OpenExam project.
         */
        const TYPE_OPEN_EXAM_PROJECT = 'op';
        /**
         * Import file containing student registrations.
         */
        const TYPE_STUDENT_REGISTRATIONS = 'sr';

        /**
         * Get MIME type of file.
         * 
         * Uses the PECL fileinfo extension. Throws an ImportException if 
         * extension is not loaded.
         * 
         * @param string $file The input file.
         * @return string
         * @throws ImportException
         */
        public static function getMimeType($file)
        {
                if (!extension_loaded("fileinfo")) {
                        throw new ImportException("The PECL extension fileinfo is not loaded.", Error::SERVICE_UNAVAILABLE);
                }

                if (!($res = finfo_open(FILEINFO_MIME_TYPE))) {
                        throw new ImportException("Failed open fileinfo database.", Error::SERVICE_UNAVAILABLE);
                }
                if (!($mime = finfo_file($res, $file))) {
                        throw new ImportException(sprintf("Failed get MIME type of %s.", $file), Error::NOT_ACCEPTABLE);
                }
                if (!finfo_close($res)) {
                        throw new ImportException("Failed close fileinfo database.", Error::SERVICE_UNAVAILABLE);
                }

                return $mime;
        }

        /**
         * Factory function returning an importer.
         * 
         * @param string $from The import source. One of the TYPE_XXX constants.
         * @param string $type File type identifier (e.g. excel2003 or xls).
         * @param string $name The file name.
         * @return ImportBase
         * @throws ImportException
         */
        public static function create($from, $type = null, $name = null, $extension = null)
        {
                if (!isset($name)) {
                        $name = $_FILES['file']['name'];
                }
                if (!isset($extension)) {
                        $extension = substr(strrchr($name, "."), 1);
                }

                if ($from == self::TYPE_PING_PONG) {
                        switch ($extension) {
                                case "xls":
                                        return new ImportPingPongExcel();
                                case "tab":
                                case "txt":
                                        return new ImportPingPongText();
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files."), $extension), Error::NOT_ACCEPTABLE);
                        }
                } elseif ($from == self::TYPE_OPEN_EXAM_QUESTIONS || $from == self::TYPE_OPEN_EXAM_PROJECT) {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExamXml();
                        }
                } elseif ($from == self::TYPE_STUDENT_REGISTRATIONS) {
                        if ($type != -1) {
                                $find = $type;
                        } else {
                                $find = $extension;
                        }
                        switch ($find) {
                                case "excel2007":
                                case "xlsx":
                                        return new ImportStudentsExcel2007();
                                case "excel2003":
                                case "xml":
                                        return new ImportStudentsExcel2003Xml();
                                case "excel5":
                                case "excel97":
                                case "xls":
                                        return new ImportStudentsExcel97();
                                case "oocalc":
                                case "ods":
                                case "ots":
                                        return new ImportStudentsOpenDocument();
                                case "gnumeric":
                                        return new ImportStudentsGnumeric();
                                case "tab":
                                case "tsv":
                                case "txt":
                                        return new ImportStudentsTextTab();
                                case "csv":
                                        return new ImportStudentsTextCsv();
                                case "htm":
                                case "html":
                                        return new ImportStudentsHtml();
                                case "slk":
                                case "sylk":
                                        return new ImportStudentsSylk();
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files."), $extension), Error::NOT_ACCEPTABLE);
                        }
                }
        }

}
