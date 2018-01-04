<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Course.php
// Created: 2017-01-04 03:23:28
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter\Uppdok;

use const INFO_CGI_FIELD_SEPARATOR;
use const INFO_CGI_FIELD_USER;
use const INFO_CGI_RECORD_SEPARATOR;

if (!defined('INFO_CGI_RECORD_SEPARATOR')) {
        define('INFO_CGI_RECORD_SEPARATOR', "\n");
}
if (!defined('INFO_CGI_FIELD_SEPARATOR')) {
        define('INFO_CGI_FIELD_SEPARATOR', ";");
}

/**
 * Represent an UPPDOK course.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Course
{

        /**
         * The course code.
         * @var string 
         */
        private $_course;
        /**
         * The year.
         * @var int 
         */
        private $_year;
        /**
         * The semester.
         * @var int 
         */
        private $_termin;

        /**
         * Constructor.
         * @param string $course The course code.
         * @param int $year The year.
         * @param int $termin The semester.
         */
        public function __construct($course, $year, $termin)
        {
                $this->_course = $course;
                $this->_termin = $termin;
                $this->_year = $year;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_course);
                unset($this->_termin);
                unset($this->_year);
        }

        /**
         * Get course members.
         * @param Connection $connection The server connection.
         */
        public function getContent($connection, $compact)
        {
                // 
                // Create URL:
                // 
                $url = sprintf(Connection::TARGET, $connection->hostname(), $connection->port(), $this->_course, $this->_year, $this->_termin, $connection->user(), $connection->pass());

                // 
                // Get content using URL:
                // 
                $content = $connection->find($url);

                //
                // We are only interested in UpUnet-S identities, so we cut it out and
                // discard all other information (p-nr and registration date).
                //
                $result = array();
                $lines = explode(INFO_CGI_RECORD_SEPARATOR, $content);

                foreach ($lines as $line) {
                        $arr = explode(INFO_CGI_FIELD_SEPARATOR, $line);
                        if ($arr[0] == 1) {
                                if ($compact) {
                                        array_push($result, $arr[INFO_CGI_FIELD_USER]);
                                } else {
                                        array_push($result, new Record($arr));
                                }
                        }
                }

                return $result;
        }

}
