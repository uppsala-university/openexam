<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Data.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService\Uppdok;

use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\ServiceConnection;
use Phalcon\Mvc\User\Component;

if (!defined('INFO_CGI_DEBUG')) {
        define('INFO_CGI_DEBUG', false);
}
if (!defined('INFO_CGI_VERBOSE')) {
        define('INFO_CGI_VERBOSE', false);
}
if (!defined('INFO_CGI_RECORD_SEPARATOR')) {
        define('INFO_CGI_RECORD_SEPARATOR', "\n");
}
if (!defined('INFO_CGI_FIELD_SEPARATOR')) {
        define('INFO_CGI_FIELD_SEPARATOR', ";");
}
if (!defined('INFO_CGI_FIELD_USER')) {
        define('INFO_CGI_FIELD_USER', 1);
}
if (!defined('INFO_CGI_FIELD_NAME')) {
        define('INFO_CGI_FIELD_NAME', 3);
}
if (!defined('INFO_CGI_FIELD_EXPIRES')) {
        define('INFO_CGI_FIELD_EXPIRES', 3);
}
if (!defined('INFO_CGI_FIELD_SOCIAL_NUMBER')) {
        define('INFO_CGI_FIELD_SOCIAL_NUMBER', 5);
}
if (!defined('INFO_CGI_FIELD_MAIL')) {
        define('INFO_CGI_FIELD_MAIL', 7);
}

/**
 * UPPDOK (InfoCGI) data service.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Data extends Component
{

        /**
         * URL template for GET request.
         */
        const TARGET = "http://%s:%d/getreg?typ=kurs&kod=%s&termin=%d%d&from=%s&pass=%s";

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
        public function __construct($connection)
        {
                $this->_connection = $connection;

                $this->_year = self::getCurrentYear();
                $this->_termin = self::getCurrentSemester();
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
        public function setCompactMode($enable = true)
        {
                $this->_compact = $enable;
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
         * @param string $course The course code.
         * @param type $year The requested year.
         * @param type $termin The requested semester.
         * @return array
         * @throws Exception
         */
        public function members($course, $year = 0, $termin = 0)
        {
                if (!extension_loaded("curl")) {
                        throw new Exception("The curl extension is not loaded");
                }

                $curl = curl_init();
                if (!isset($curl)) {
                        throw new Exception("Failed initialize cURL");
                }

                if ($year == 0) {
                        $year = $this->_year;
                }
                if ($termin == 0) {
                        $termin = $this->_termin;
                }


                $url = sprintf(self::TARGET, $this->_connection->hostname(), $this->_connection->port(), $course, $year, $termin, $this->_connection->user(), $this->_connection->pass());

                if (INFO_CGI_DEBUG) {
                        curl_setopt($curl, CURLOPT_HEADER, 1);
                }
                if (INFO_CGI_VERBOSE) {
                        curl_setopt($curl, CURLOPT_VERBOSE, 1);
                }

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $content = curl_exec($curl);
                $error = curl_error($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);

                if (!$content || $info['http_code'] != 200) {
                        $this->logger->system->error(sprintf("Failed fetch membership information from UPPDOK data: %s", $error));
                        throw new Exception($this->tr->_("There was a problem talking to the directory service, course information is unavailable due to network or configuration problems"));
                }

                //
                // We are only interested in UpUnet-S identities, so we cut it out and
                // discard all other information (p-nr and registration date).
                //
                        $result = array();
                $lines = explode(INFO_CGI_RECORD_SEPARATOR, $content);
                foreach ($lines as $line) {
                        $arr = explode(INFO_CGI_FIELD_SEPARATOR, $line);
                        if ($arr[0] == 1) {
                                if ($this->_compact) {
                                        array_push($result, $arr[INFO_CGI_FIELD_USER]);
                                } else {
                                        array_push($result, new Record($arr));
                                }
                        }
                }
                return $result;
        }

}
