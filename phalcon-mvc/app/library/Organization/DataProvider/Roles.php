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
// File:    Roles.php
// Created: 2016-05-16 14:11:34
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

use OpenExam\Library\Organization\OrganizationUnit;
use OpenExam\Library\Security\Roles as SecurityRoles;
use Phalcon\Mvc\User\Component;

/**
 * The roles data provider.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Roles extends Component
{

        /**
         * The exams filter.
         * @var array
         */
        private $_filter;
        /**
         * Query conditions.
         * @var string
         */
        private $_conditions;
        /**
         * Cache key.
         * @var string
         */
        private $_cachekey;
        /**
         * Cache data lifetime.
         * @var int 
         */
        private $_lifetime;
        /**
         * @var array
         */
        private $_data;

        /**
         * Constructor.
         * @param array $filter The exams filter.
         */
        public function __construct($filter)
        {
                $this->_filter = $filter;
                $this->_conditions = $this->getConditions();

                $this->_cachekey = $this->createCacheKey();
                $this->_lifetime = OrganizationUnit::CACHE_ENTRY_LIFETIME;

                $this->setData();
        }

        /**
         * Get roles data based on current filter.
         * 
         * The returned data has this format:
         * <code>
         * array(
         *      'role' => array(
         *              'label' => string,
         *              'count' => int
         *      ),
         *     ...
         * )
         * </code>
         * 
         * @return array
         */
        public function getData()
        {
                return $this->_data;
        }

        /**
         * Get total number of roles.
         * @return int
         */
        public function getSize()
        {
                return $this->_data[SecurityRoles::SYSTEM]['count'];
        }

        private function setData()
        {
                if ($this->cache->exists($this->_cachekey, $this->_lifetime)) {
                        $this->_data = $this->cache->get($this->_cachekey, $this->_lifetime);
                        return;
                }
                if (($this->_data = $this->findData())) {
                        $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
                }
        }

        private function findData()
        {
                $roles = array();

                $roles[] = new Role($this->_conditions, SecurityRoles::SYSTEM, $this->tr->_('System Users'));
                $roles[] = new Role($this->_conditions, SecurityRoles::CONTRIBUTOR, $this->tr->_('Contributors'));
                $roles[] = new Role($this->_conditions, SecurityRoles::CORRECTOR, $this->tr->_('Correctors'));
                $roles[] = new Role($this->_conditions, SecurityRoles::CREATOR, $this->tr->_('Creators'));
                $roles[] = new Role($this->_conditions, SecurityRoles::DECODER, $this->tr->_('Decoders'));
                $roles[] = new Role($this->_conditions, SecurityRoles::INVIGILATOR, $this->tr->_('Invigilators'));
                $roles[] = new Role($this->_conditions, SecurityRoles::STUDENT, $this->tr->_('Students'));

                if (count($this->_filter) == 0) {
                        $roles[] = new Role($this->_conditions, SecurityRoles::ADMIN, $this->tr->_('Admins'));
                        $roles[] = new Role($this->_conditions, SecurityRoles::TEACHER, $this->tr->_('Teachers'));
                }

                $result = array();

                foreach ($roles as $role) {
                        $result[$role->getRole()] = array(
                                'label' => $role->getName(),
                                'count' => $role->getSize()
                        );
                }

                return $result;
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
                        case SecurityRoles::SYSTEM:
                                return new Role($this->_conditions, $role, $this->tr->_('System Users'));
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

        private function createCacheKey()
        {
                return sprintf("organization-roles-%s", md5($this->_conditions));
        }

}
