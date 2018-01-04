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
// File:    Departments.php
// Created: 2017-04-19 11:26:14
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Mvc\User\Component;

/**
 * The user departments class.
 * 
 * Some users have multiple departments. This class tries to ease the pain
 * of querying and collecting that information.
 * 
 * @property-read string $name The primary department name.
 * @property-read string $code The primary department code.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Departments extends Component
{

        /**
         * The department attribute name.
         */
        const ATTR_DEP_NAME = 'department';
        /**
         * The department code attribute.
         */
        const ATTR_DEP_CODE = 'departmentNumber';

        /**
         * The user departments.
         * @var array 
         */
        private $_departments;
        /**
         * The affected principal name.
         * @var string 
         */
        private $_principal;
        /**
         *
         * @var array 
         */
        private $_first;

        /**
         * Constructor.
         * 
         * If user principal name is null, then current logged on user is
         * used as target user for catalog queries.
         * 
         * @param string $principal The user principal name.
         */
        public function __construct($principal = null)
        {
                if (isset($principal)) {
                        $this->_principal = $principal;
                } else {
                        $this->_principal = $this->user->getPrincipalName();
                }

                $this->setDepartments();
                $this->setFirst();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_departments);
                unset($this->_principal);
        }

        public function __get($propertyName)
        {
                switch ($propertyName) {
                        case 'name':
                                return $this->_first['name'];
                        case 'code':
                                return $this->_first['code'];
                        default:
                                return parent::__get($propertyName);
                }
        }

        /**
         * Get all departments.
         * 
         * The returned array is keyed by department codes.
         * 
         * @return array
         */
        public function getDepartments()
        {
                return $this->_departments;
        }

        /**
         * Get department codes.
         * @return array
         */
        public function getCodes()
        {
                return array_keys($this->_departments);
        }

        /**
         * Get first code/name entry.
         * @return array
         */
        public function getFirst()
        {
                return $this->_first;
        }

        /**
         * Set primary department and department code.
         */
        private function setFirst()
        {
                if (count($this->_departments) == 0) {
                        $this->_first = array(
                                'code' => null,
                                'name' => null
                        );
                } else {

                        $code = key($this->_departments);
                        $name = current($this->_departments[$code]);

                        $this->_first = array(
                                'code' => $code,
                                'name' => $name
                        );
                }
        }

        /**
         * Get department data.
         * @param string $code The department code.
         * @return array
         */
        public function getDepartment($code)
        {
                if (isset($this->_departments[$code])) {
                        return $this->_departments[$code];
                }
        }

        /**
         * Check if user has multiple departments.
         * @return boolean
         */
        public function hasMultiple()
        {
                return count($this->_departments) > 1;
        }

        /**
         * Set departments data.
         */
        private function setDepartments()
        {
                $needle = $this->_principal;
                $search = Principal::ATTR_PN;
                $filter = array('attr' => array(self::ATTR_DEP_NAME, self::ATTR_DEP_CODE));

                if (($principals = $this->catalog->getPrincipals($needle, $search, $filter))) {
                        $this->_departments = array();
                        foreach ($principals as $principal) {
                                if (isset($principal->attr[self::ATTR_DEP_NAME]) &&
                                    isset($principal->attr[self::ATTR_DEP_CODE])) {
                                        $attr = $principal->attr[self::ATTR_DEP_NAME];
                                        $code = $principal->attr[self::ATTR_DEP_CODE];

                                        $this->setDepartment(current($code), array_unique($attr));
                                }
                        }
                }
        }

        /**
         * Set department data.
         * @param string $code The department code.
         * @param array $attr The department data.
         */
        private function setDepartment($code, $attr)
        {
                if (!isset($this->_departments[$code])) {
                        $this->_departments[$code] = $attr;
                } else {
                        $this->_departments[$code] = array_merge($this->_departments[$code], $attr);
                }
        }

}
