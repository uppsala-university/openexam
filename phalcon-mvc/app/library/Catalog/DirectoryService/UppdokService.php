<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UppdokService.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

/**
 * Prevent namespace pollution:
 */

namespace OpenExam\Library\Catalog\DirectoryService\Uppdok {

        use OpenExam\Library\Catalog\Exception;
        use OpenExam\Library\Catalog\Principal;
        use Phalcon\Mvc\User\Component;

        if (!defined('INFO_CGI_RECORD_SEPARATOR')) {
                define('INFO_CGI_RECORD_SEPARATOR', "\n");
        }
        if (!defined('INFO_CGI_FIELD_SEPARATOR')) {
                define('INFO_CGI_FIELD_SEPARATOR', ";");
        }
        if (!defined('INFO_CGI_DEBUG')) {
                define('INFO_CGI_DEBUG', false);
        }
        if (!defined('INFO_CGI_VERBOSE')) {
                define('INFO_CGI_VERBOSE', false);
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
        if (!defined('INFO_CGI_SERVER')) {
                define('INFO_CGI_SERVER', 'localhost');
        }
        if (!defined('INFO_CGI_PORT')) {
                define('INFO_CGI_PORT', 108);
        }

        /**
         * Represent one UPPDOK record.
         * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
         */
        class UppdokRecord
        {

                /**
                 * The record data.
                 * @var array 
                 */
                private $data = array();

                /**
                 * Constructor.
                 * @param array $data The UPPDOK record.
                 */
                public function __construct($data)
                {
                        $this->data = array_map('utf8_encode', $data);
                }

                /**
                 * Get user from data record.
                 * @return string
                 */
                public function getUser()
                {
                        return $this->data[INFO_CGI_FIELD_USER];
                }

                /**
                 * Get name from data record.
                 * @return string
                 */
                public function getName()
                {
                        return $this->data[INFO_CGI_FIELD_NAME];
                }

                /**
                 * Get complete record.
                 * @return array
                 */
                public function getData()
                {
                        return $this->data;
                }

                /**
                 * Get user principal object.
                 * @param string $domain The search domain.
                 * @param array $attributes The attributes to return.
                 * @return Principal
                 */
                public function getPrincipal($domain, $attributes)
                {
                        $principal = new Principal();

                        if (in_array(Principal::ATTR_NAME, $attributes)) {
                                $principal->name = $this->data[INFO_CGI_FIELD_NAME];
                        }
                        if (in_array(Principal::ATTR_MAIL, $attributes)) {
                                $principal->mail[] = $this->data[INFO_CGI_FIELD_MAIL];
                        }
                        if (in_array(Principal::ATTR_PN, $attributes)) {
                                $principal->principal = $this->data[INFO_CGI_FIELD_USER] . '@' . $domain;
                        }
                        if (in_array(Principal::ATTR_PNR, $attributes)) {
                                $principal->pnr = $this->data[INFO_CGI_FIELD_SOCIAL_NUMBER];
                        }
                        if (in_array(Principal::ATTR_UID, $attributes)) {
                                $principal->uid = $this->data[INFO_CGI_FIELD_USER];
                        }
                        if (in_array(Principal::ATTR_GN, $attributes)) {
                                $principal->gn = trim(strstr($this->data[INFO_CGI_FIELD_NAME], ' ', true));
                        }
                        if (in_array(Principal::ATTR_SN, $attributes)) {
                                $principal->sn = trim(strstr($this->data[INFO_CGI_FIELD_NAME], ' ', false));
                        }

                        return $principal;
                }

                /**
                 * Get data at position index.
                 * @param int $index The index position.
                 * @return string
                 */
                public function getField($index)
                {
                        if ($index < count($this->data)) {
                                return $this->data[$index];
                        } else {
                                return null;
                        }
                }

        }

        /**
         * UPPDOK (InfoCGI) data service.
         * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
         */
        class UppdokData extends Component
        {

                /**
                 * The GET request template.
                 */
                const url = "http://%s:%d/getreg?typ=kurs&kod=%s&termin=%d%d&from=%s&pass=%s";

                /**
                 * Requested year.
                 * @var int 
                 */
                private $year;
                /**
                 * Requested termin (semester).
                 * @var int 
                 */
                private $termin;
                /**
                 * Compact output.
                 * @var bool 
                 */
                private $compact = true;
                /**
                 * The InfoCGI service username.
                 * @var string 
                 */
                private $user;
                /**
                 * The InfoCGI service password.
                 * @var string 
                 */
                private $pass;
                /**
                 * The InfoCGI service hostname.
                 * @var string 
                 */
                private $host;
                /**
                 * The InfoCGI service port.
                 * @var int 
                 */
                private $port;

                /**
                 * Constructor.
                 * @param string $user The InfoCGI service username.
                 * @param string $pass The InfoCGI service password.
                 * @param string $host The InfoCGI service hostname.
                 * @param int $port The InfoCGI service port.
                 * @throws Exception
                 */
                public function __construct($user, $pass, $host = INFO_CGI_SERVER, $port = INFO_CGI_PORT)
                {
                        if (!isset($user) || !isset($pass) || !isset($host)) {
                                throw new Exception("Missing username, password or server name.");
                        }

                        $this->user = $user;
                        $this->pass = $pass;
                        $this->host = $host;
                        $this->port = $port;

                        $this->year = self::getCurrentYear();
                        $this->termin = self::getCurrentSemester();
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
                        $this->year = $year;
                }

                /**
                 * Get default year.
                 * @return int
                 */
                public function getYear()
                {
                        return $this->year;
                }

                /**
                 * Set default semester.
                 * @param int $termin The semester (1 or 2).
                 */
                public function setSemester($termin)
                {
                        $this->termin = $termin;
                }

                /**
                 * Get default semester.
                 * @return int
                 */
                public function getSemester()
                {
                        return $this->termin;
                }

                /**
                 * Set compact output mode.
                 * @param bool $enable Enabled if true.
                 */
                public function setCompactMode($enable = true)
                {
                        $this->compact = $enable;
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
                                $year = $this->year;
                        }
                        if ($termin == 0) {
                                $termin = $this->termin;
                        }


                        $url = sprintf(self::url, $this->host, $this->port, $course, $year, $termin, $this->user, $this->pass);

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
                                        if ($this->compact) {
                                                array_push($result, $arr[INFO_CGI_FIELD_USER]);
                                        } else {
                                                array_push($result, new UppdokRecord($arr));
                                        }
                                }
                        }
                        return $result;
                }

        }

}

namespace OpenExam\Library\Catalog\DirectoryService {

        use OpenExam\Library\Catalog\DirectoryService\Uppdok\UppdokData;
        use OpenExam\Library\Catalog\ServiceAdapter;

        /**
         * UPPDOK directory service.
         *
         * @author Anders Lövgren (QNET/BMC CompDept)
         */
        class UppdokService extends ServiceAdapter
        {

                /**
                 * The UPPDOK data service.
                 * @var UppdokData 
                 */
                private $uppdok;

                /**
                 * Constructor.
                 * @param string $user The service username.
                 * @param string $pass The service password.
                 * @param string $host The service hostname.
                 * @param int $port The service port.
                 */
                public function __construct($user, $pass, $host = INFO_CGI_SERVER, $port = INFO_CGI_PORT)
                {
                        $this->uppdok = new UppdokData($user, $pass, $host, $port);
                        $this->uppdok->setCompactMode(false);
                }

                /**
                 * Get members of group.
                 * @param string $group The group name.
                 * @param string $domain Restrict search to domain.
                 * @param array $attributes The attributes to return.
                 * @return Principal[]
                 */
                public function getMembers($group, $domain, $attributes)
                {
                        $result = array();

                        foreach ($this->uppdok->members($group) as $member) {
                                $result[] = $member->getPrincipal($domain, $attributes);
                        }
                        
                        return $result;
                }

        }

}
