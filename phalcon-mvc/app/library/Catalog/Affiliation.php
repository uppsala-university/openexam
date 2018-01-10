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
// File:    Affilation.php
// Created: 2015-03-16 00:31:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Mvc\User\Component;

/**
 * User affiliation class.
 * 
 * This class provides user affiliation information from the catalog service.
 * Notice that properties (e.g. student) is unrelated to roles in the system.
 * 
 * The class uses lazy loading. The user affiliation is loaded on demand from
 * the catalog whenever one of the member functions is called.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Affiliation extends Component
{

        /**
         * The student affiliation.
         */
        const STUDENT = 'student';
        /**
         * The employee affiliation.
         */
        const EMPLOYEE = 'employee';
        /**
         * The faculty affiliation.
         */
        const FACULTY = 'faculty';
        /**
         * The member affiliation.
         */
        const MEMBER = 'member';
        /**
         * The staff affiliation.
         */
        const STAFF = 'staff';

        /**
         * All user affiliations.
         * @var array 
         */
        private $_affiliations;
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
                unset($this->_affiliations);
                unset($this->_principal);
        }

        /**
         * Return true if user has student affiliation.
         * @return boolean
         */
        public function isStudent()
        {
                return $this->hasFunction(self::STUDENT);
        }

        /**
         * Return true if user has employee affiliation.
         * @return boolean
         */
        public function isEmployee()
        {
                return $this->hasFunction(self::EMPLOYEE);
        }

        /**
         * Return true if user has faculty affiliation.
         * @return boolean
         */
        public function isFaculty()
        {
                return $this->hasFunction(self::FACULTY);
        }

        /**
         * Return true if user has member affiliation.
         * @return boolean
         */
        public function isMember()
        {
                return $this->hasFunction(self::MEMBER);
        }

        /**
         * Return true if user has staff affiliation.
         * @return boolean
         */
        public function isStaff()
        {
                return $this->hasFunction(self::STAFF);
        }

        /**
         * Return true if user has requested affiliation.
         * @return boolean
         */
        public function hasFunction($role)
        {
                if (!isset($this->_affiliations)) {
                        $this->setAffilations();
                }

                return in_array($role, $this->_affiliations);
        }

        /**
         * Get all user affiliations.
         * @return array
         */
        public function getAffiliations()
        {
                if (!isset($this->_affiliations)) {
                        $this->setAffilations();
                }

                return $this->_affiliations;
        }

        /**
         * Set affiliations data from catalog.
         */
        private function setAffilations()
        {
                $this->_affiliations = $this->catalog->getAffiliation($this->_principal);
        }

}
