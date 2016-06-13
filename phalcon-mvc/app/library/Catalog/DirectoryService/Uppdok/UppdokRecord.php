<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UppdokRecord.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\DirectoryService\Uppdok;

use OpenExam\Library\Catalog\Principal;

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
 * Represent one UPPDOK record.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UppdokRecord
{

        /**
         * The record data.
         * @var array 
         */
        private $_data = array();

        /**
         * Constructor.
         * @param array $data The UPPDOK record.
         */
        public function __construct($data)
        {
                $this->_data = array_map('utf8_encode', $data);
        }

        /**
         * Get user from data record.
         * @return string
         */
        public function getUser()
        {
                return $this->_data[INFO_CGI_FIELD_USER];
        }

        /**
         * Get name from data record.
         * @return string
         */
        public function getName()
        {
                return $this->_data[INFO_CGI_FIELD_NAME];
        }

        /**
         * Get complete record.
         * @return array
         */
        public function getData()
        {
                return $this->_data;
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
                        $principal->name = $this->_data[INFO_CGI_FIELD_NAME];
                }
                if (in_array(Principal::ATTR_MAIL, $attributes)) {
                        $principal->mail[] = $this->_data[INFO_CGI_FIELD_MAIL];
                }
                if (in_array(Principal::ATTR_PN, $attributes)) {
                        $principal->principal = $this->_data[INFO_CGI_FIELD_USER] . '@' . $domain;
                }
                if (in_array(Principal::ATTR_PNR, $attributes)) {
                        $principal->pnr = $this->_data[INFO_CGI_FIELD_SOCIAL_NUMBER];
                }
                if (in_array(Principal::ATTR_UID, $attributes)) {
                        $principal->uid = $this->_data[INFO_CGI_FIELD_USER];
                }
                if (in_array(Principal::ATTR_GN, $attributes)) {
                        $principal->gn = trim(strstr($this->_data[INFO_CGI_FIELD_NAME], ' ', true));
                }
                if (in_array(Principal::ATTR_SN, $attributes)) {
                        $principal->sn = trim(strstr($this->_data[INFO_CGI_FIELD_NAME], ' ', false));
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
                if ($index < count($this->_data)) {
                        return $this->_data[$index];
                } else {
                        return null;
                }
        }

}
