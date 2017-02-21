<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
         * Get and set affilations data from catalog.
         */
        private function setAffilations()
        {
                $this->_affiliations = array();
                $affiliations = $this->catalog->getAttributes(Principal::ATTR_AFFIL, $this->_principal);

                foreach ($affiliations as $data) {
                        $this->_affiliations = array_merge($this->_affiliations, $data[Principal::ATTR_AFFIL]);
                }

                $this->_affiliations = array_unique($this->_affiliations);
        }

}
