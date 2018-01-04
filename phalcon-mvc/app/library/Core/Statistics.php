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
// File:    Statistics.php
// Created: 2016-04-28 19:38:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

use OpenExam\Library\Organization\DataProvider\Exams;
use OpenExam\Library\Organization\DataProvider\Role;
use OpenExam\Library\Organization\DataProvider\Roles;
use OpenExam\Library\Organization\DataProvider\Users;
use OpenExam\Library\Organization\Division;
use OpenExam\Library\Organization\Organization;
use OpenExam\Library\Organization\OrganizationUnit;
use Phalcon\Mvc\User\Component;

/**
 * System statistics.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Statistics extends Component
{

        /**
         * The organization unit.
         * @var OrganizationUnit
         */
        private $_orgunit;

        /**
         * Constructor.
         * @param string $division The division name.
         */
        public function __construct($division = null)
        {
                if (isset($division)) {
                        $this->_orgunit = new Division($division);
                } else {
                        $this->_orgunit = new Organization($this->config->user->orgname);
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_orgunit);
        }

        /**
         * Get organization unit name.
         * @return string
         */
        public function getName()
        {
                return $this->_orgunit->getName();
        }

        /**
         * Get organization unit object.
         * @return OrganizationUnit
         */
        public function getObject()
        {
                return $this->_orgunit;
        }

        /**
         * Get child objects.
         * 
         * This function either returns an array of child organization units
         * or false of children are not supported.
         * 
         * @return boolean|OrganizationUnit[]
         */
        public function getChildren()
        {
                if ($this->_orgunit->hasChildren()) {
                        return $this->_orgunit->getChildren();
                } else {
                        return false;
                }
        }

        /**
         * Get data for this organization unit.
         * @return array
         */
        public function getData()
        {
                return $this->_orgunit->getData();
        }

        /**
         * Get users data provider.
         * @return Users
         */
        public function getUsers()
        {
                return $this->_orgunit->getUsers();
        }

        /**
         * Get exams data provider.
         * @return Exams
         */
        public function getExams()
        {
                return $this->_orgunit->getExams();
        }

        /**
         * Get roles data provider.
         * @return Roles
         */
        public function getRoles()
        {
                return $this->_orgunit->getRoles();
        }

        /**
         * Get data provider for this role.
         * @param string $role The role name.
         * @return Role
         */
        public function getRole($role)
        {
                return $this->_orgunit->getRoles()->getProvider($role);
        }

        /**
         * Get summary data.
         * @return array
         */
        public function getSummary()
        {
                $roles = $this->_orgunit->getRoles();
                $exams = $this->_orgunit->getExams();
                $users = $this->_orgunit->getUsers();

                $summary = array(
                        'roles' => array(
                                'label' => $this->tr->_('Roles'),
                                'count' => $roles->getSize(),
                                'data'  => $roles->getData()
                        ),
                        'exams' => array(
                                'label' => $this->tr->_('Exams'),
                                'count' => $exams->getSize()
                        ),
                        'users' => array(
                                'label'     => $this->tr->_('Users'),
                                'total'     => array(
                                        'label' => $this->tr->_('Total'),
                                        'count' => $users->getSize()
                                ),
                                'employees' => array(
                                        'label' => $this->tr->_('Employees'),
                                        'count' => $users->getEmployees()->getSize()
                                ),
                                'students'  => array(
                                        'label' => $this->tr->_('Students'),
                                        'count' => $users->getStudents()->getSize()
                                )
                        )
                );

                unset($roles);
                unset($exams);
                unset($users);

                return $summary;
        }

}
