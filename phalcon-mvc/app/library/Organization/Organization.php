<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
