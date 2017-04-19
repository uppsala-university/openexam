<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_departments);
                unset($this->_principal);
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
                if (!isset($this->_departments)) {
                        $this->setDepartments();
                }

                return $this->_departments;
        }

        /**
         * Get department codes.
         * @return array
         */
        public function getCodes()
        {
                if (!isset($this->_departments)) {
                        $this->setDepartments();
                }

                return array_keys($this->_departments);
        }

        /**
         * Get department data.
         * @param string $code The department code.
         * @return array
         */
        public function getDepartment($code)
        {
                if (!isset($this->_departments)) {
                        $this->setDepartments();
                }

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
                if (!isset($this->_departments)) {
                        $this->setDepartments();
                }

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
