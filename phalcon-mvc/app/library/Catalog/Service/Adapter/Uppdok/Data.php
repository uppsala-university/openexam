<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    Data.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter\Uppdok;

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\ServiceConnection;
use Phalcon\Mvc\User\Component;

/**
 * UPPDOK (InfoCGI) data service.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Data extends Component
{

        /**
         * Requested year.
         * @var int 
         */
        private $_year;
        /**
         * Requested termin (semester).
         * @var int 
         */
        private $_termin;
        /**
         * Compact output.
         * @var bool 
         */
        private $_compact = true;
        /**
         * The UPPDOK service connection.
         * @var Connection 
         */
        private $_connection;

        /**
         * Constructor.
         * 
         * @param Connection $connection The UPPDOK service connection.
         */
        public function __construct($connection = null)
        {
                $this->_connection = $connection;

                $this->_year = self::getCurrentYear();
                $this->_termin = self::getCurrentSemester();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_connection);
        }

        /**
         * Get current year.
         * @return int
         */
        public static function getCurrentYear()
        {
                return date('Y', time());
        }

        /**
         * Get current semester.
         * @return int
         */
        public static function getCurrentSemester()
        {
                if (date('m', time()) < 7) {
                        return 1;
                } else {
                        return 2;
                }
        }

        /**
         * Set default year for query.
         * @param int $year The year.
         */
        public function setYear($year)
        {
                $this->_year = $year;
        }

        /**
         * Get default year.
         * @return int
         */
        public function getYear()
        {
                return $this->_year;
        }

        /**
         * Set default semester.
         * @param int $termin The semester (1 or 2).
         */
        public function setSemester($termin)
        {
                $this->_termin = $termin;
        }

        /**
         * Get default semester.
         * @return int
         */
        public function getSemester()
        {
                return $this->_termin;
        }

        /**
         * Set compact output mode.
         * @param bool $enable Enabled if true.
         */
        public function setCompactMode($enable)
        {
                $this->_compact = $enable;
        }

        /**
         * Set service connection.
         * @param Connection $connection The service connection.
         */
        public function setConnection($connection)
        {
                $this->_connection = $connection;
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return $this->_connection;
        }

        /**
         * Get all members on this course. The course argument is an 
         * UPPDOK code (i.e. 1AB234). The function should return an 
         * array of UpUnet-S identities (a.k.a. CAS-ID).
         * 
         * If year or termin is unset, then they defaults to current
         * year/semester or default values previously set.
         * 
         * @param string $code The course code.
         * @param type $year The requested year.
         * @param type $termin The requested semester.
         * @return array
         * @throws Exception
         */
        public function members($code, $year = 0, $termin = 0)
        {
                if ($year == 0) {
                        $year = $this->_year;
                }
                if ($termin == 0) {
                        $termin = $this->_termin;
                }
                
                $course = new Course($code, $year, $termin);
                $result = $course->getContent($this->_connection, $this->_compact);

                return $result;
        }

}
