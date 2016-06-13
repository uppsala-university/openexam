<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Orgnanization.php
// Created: 2016-05-16 06:12:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization;

use OpenExam\Library\Organization\DataProvider\Exams as ExamsDataProvider;
use OpenExam\Library\Organization\DataProvider\Roles as RolesDataProvider;
use OpenExam\Library\Organization\DataProvider\Users as UsersDataProvider;

/**
 * The organization class.
 * 
 * This class represent an organization. Currently an organization is the
 * topmost level in the organization hierarchy.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Organization extends OrganizationUnit
{

        /**
         * Constructor.
         * @param string $name The organization name.
         */
        public function __construct($name = "global")
        {
                parent::__construct($name);
        }

        /**
         * Check if this object might have child object.
         * @return boolean
         */
        public function hasChildren()
        {
                return true;
        }

        /**
         * Get all divisions.
         * @return Division[]
         */
        public function getChildren()
        {
                return Division::getDivisions();
        }

        /**
         * Get exams data provider for this organization.
         * @return ExamsDataProvider
         */
        public function getExams()
        {
                return new ExamsDataProvider(array());
        }

        /**
         * Get roles data provider for this organization.
         * @return RolesDataProvider
         */
        public function getRoles()
        {
                return new RolesDataProvider(array());
        }

        /**
         * Get users data provider for this organization.
         * @return UsersDataProvider
         */
        public function getUsers()
        {
                return new UsersDataProvider(array());
        }

        protected function createCacheKey()
        {
                return sprintf("organization-global-%s", md5($this->_name));
        }

}
