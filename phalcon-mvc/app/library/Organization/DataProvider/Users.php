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
// File:    Users.php
// Created: 2016-05-13 03:52:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

use OpenExam\Library\Organization\DataProvider\Role as RoleDataProvider;
use OpenExam\Library\Security\Roles as SecurityRoles;
use Phalcon\Mvc\User\Component;

/**
 * The users data provider.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Users extends Component
{

        /**
         * Enum constant for student type.
         */
        const TYPE_STUDENT = 'student';
        /**
         * Enum constant for employee type.
         */
        const TYPE_EMPLOYEE = 'employee';

        /**
         * The exams filter.
         * @var array
         */
        private $_filter = false;
        /**
         * Query conditions.
         * @var string 
         */
        private $_conditions;
        /**
         * @var RoleDataProvider 
         */
        private $_data;

        /**
         * Constructor.
         * @param array $filter The exams filter.
         */
        public function __construct($filter)
        {
                $this->setFilter($filter);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_conditions);
                unset($this->_data);
                unset($this->_filter);
        }

        /**
         * Decorate returned users with name and mail from catalog service.
         * @param boolean $enable Enable or disable.
         */
        public function addDecoration($enable = true)
        {
                $this->_data->addDecoration($enable);
        }

        /**
         * Get whether returned user should be decorated.
         * @return boolean
         * @see addDecoration
         */
        public function hasDecoration()
        {
                return $this->_data->hasDecoration();
        }

        /**
         * Set exams filter.
         * @param array $filter The exams filter.
         */
        public function setFilter($filter)
        {
                if ($this->_filter == false || $this->_filter != $filter) {
                        $this->_filter = $filter;
                        $this->_conditions = $this->getConditions();
                        $this->_data = new Role($this->_conditions, SecurityRoles::SYSTEM, $this->tr->_('System Users'));
                }
        }

        /**
         * Get exams filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_filter;
        }

        /**
         * Get number of users matching current filter.
         * @return int
         */
        public function getSize()
        {
                return $this->_data->getSize();
        }

        /**
         * Get users data based on current filter.
         * 
         * The format of returned entries are:
         * <code>
         * array(
         *      'user' => string,       // The user principal name.
         *      'type' => string,       // User type (student or employee).
         *      'name' => string,       // Common name from catalog service.
         *      'mail' => string        // Primary email address from catalog service.
         * )
         * </code>
         * 
         * The employee type is a bit missleading as even employees can be
         * students on one or more exams.
         * 
         * @return array
         */
        public function getData()
        {
                return $this->_data->getData();
        }

        /**
         * Get object name (translated).
         * @return string
         */
        public function getName()
        {
                return $this->tr->_("Users");
        }

        /**
         * Get employees data provider.
         * @return Employees
         */
        public function getEmployees()
        {
                return new Employees($this);
        }

        /**
         * Get students data provider.
         * @return Students
         */
        public function getStudents()
        {
                return new Students($this);
        }

        /**
         * Get data provider for this role.
         * @param string $role The requested role.
         * @return Role
         */
        public function getProvider($role)
        {
                switch ($role) {
                        case SecurityRoles::ADMIN:
                                return new Role($this->_conditions, $role, $this->tr->_('Admins'));
                        case SecurityRoles::CONTRIBUTOR:
                                return new Role($this->_conditions, $role, $this->tr->_('Contributors'));
                        case SecurityRoles::CORRECTOR:
                                return new Role($this->_conditions, $role, $this->tr->_('Correctors'));
                        case SecurityRoles::CREATOR:
                                return new Role($this->_conditions, $role, $this->tr->_('Creators'));
                        case SecurityRoles::DECODER:
                                return new Role($this->_conditions, $role, $this->tr->_('Decoders'));
                        case SecurityRoles::INVIGILATOR:
                                return new Role($this->_conditions, $role, $this->tr->_('Invigilators'));
                        case SecurityRoles::STUDENT:
                                return new Role($this->_conditions, $role, $this->tr->_('Students'));
                        case SecurityRoles::TEACHER:
                                return new Role($this->_conditions, $role, $this->tr->_('Teachers'));
                }
        }

        /**
         * Get query conditions.
         * @return string
         */
        private function getConditions()
        {
                switch (count($this->_filter)) {
                        case 0:
                                return "published = 'Y'";
                        case 1:
                                return sprintf(
                                    "orgdiv = '%s' AND published = 'Y'", $this->_filter['division']
                                );
                        case 2:
                                return sprintf(
                                    "orgdiv = '%s' AND orgdep = '%s' AND published = 'Y'", $this->_filter['division'], $this->_filter['department']
                                );
                        case 3:
                                return sprintf(
                                    "orgdiv = '%s' AND orgdep = '%s' AND orggrp = '%s' AND published = 'Y'", $this->_filter['division'], $this->_filter['department'], $this->_filter['workgroup']
                                );
                }
        }

}
