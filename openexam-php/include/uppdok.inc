<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/uppdok.inc
// Author: Anders Lövgren
// Date:   2010-12-14
//

if (!defined('INFO_CGI_RECORD_SEPARATOR')) {
        define('INFO_CGI_RECORD_SEPARATOR', "\n");
}
if (!defined('INFO_CGI_FIELD_SEPARATOR')) {
        define('INFO_CGI_FIELD_SEPARATOR', ";");
}
if (!defined('INFO_CGI_DEBUG')) {
        define('INFO_CGI_DEBUG', true);
}
if (!defined('INFO_CGI_VERBOSE')) {
        define('INFO_CGI_VERBOSE', true);
}

class UppdokData
{
        const url = "http://%s:%d/getreg?typ=kurs&kod=%s&termin=%d%d&from=%s&pass=%s";

        private $year;
        private $termin;
        private $compact = true;

        public function __construct($year = 0, $termin = 0)
        {
                if (!defined('INFO_CGI_SERVER') ||
                        !defined('INFO_CGI_PASS') ||
                        !defined('INFO_CGI_SERVER') ||
                        !defined('INFO_CGI_PORT')) {
                        error_log("One or more configure options for InfoCGI is missing. Please check INFO_CGI_XXX in conf/config.inc");
                        throw new Exception(_("It's not possible to fetch group members, please check the system configuration."));
                }

                if ($year == 0) {
                        $this->year = self::getCurrentYear();
                }
                if ($termin == 0) {
                        $this->termin = self::getCurrentSemester();
                }
        }

        public static function getCurrentYear()
        {
                return date('Y', time());
        }

        public static function getCurrentSemester()
        {
                if (date('m', time()) < 7) {
                        return 1;
                } else {
                        return 2;
                }
        }

        public function setYear($year)
        {
                $this->year = $year;
        }

        public function getYear()
        {
                return $this->year;
        }

        public function setSemester($termin)
        {
                $this->termin = $termin;
        }

        public function getSemester()
        {
                return $this->termin;
        }

        //
        // Get all members on this course. The course argument is an UPPDOK code (i.e.
        // 1AB234). The function should return an array of UpUnet-S identities (a.k.a.
        // CAS-ID).
        //
        public function members($course)
        {
                if (!extension_loaded("curl")) {
                        throw new Exception("The curl extension is not loaded");
                }

                $curl = curl_init();
                if (!isset($curl)) {
                        throw new Exception("Failed initialize cURL");
                }

                $url = sprintf(self::url,
                                INFO_CGI_SERVER,
                                INFO_CGI_PORT,
                                $course,
                                $this->year,
                                $this->termin,
                                INFO_CGI_USER,
                                INFO_CGI_PASS);

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
                curl_close($curl);

                if (!$content) {
                        error_log(sprintf("Failed fetch membership information from UPPDOK data: %s", $error));
                        throw new Exception(_("It's not possible to fetch group members, please check the system configuration."));
                }

                //
                // We are only interested in UpUnet-S identities, so we cut it out and
                // discard all other information (p-nr and registration date).
                //
                $result = array();
                $lines = explode(INFO_CGI_RECORD_SEPARATOR, $content);
                foreach ($lines as $line) {
                        $arr = split(INFO_CGI_FIELD_SEPARATOR, $line);
                        if ($arr[0] == 1) {
                                if ($this->compact) {
                                        array_push($result, $arr[1]);
                                } else {
                                        array_push($result, $arr);
                                }
                        }
                }
                return $result;
        }

}

?>
