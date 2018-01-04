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
// File:    Record.php
// Created: 2014-10-22 04:20:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service\Adapter\Uppdok;

use OpenExam\Library\Catalog\Principal;
use const INFO_CGI_FIELD_MAIL;
use const INFO_CGI_FIELD_NAME;
use const INFO_CGI_FIELD_SOCIAL_NUMBER;
use const INFO_CGI_FIELD_USER;

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
class Record
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
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_data);
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
