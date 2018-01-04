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
// File:    Groups.php
// Created: 2017-04-11 22:26:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Group;
use OpenExam\Library\Catalog\Manager\Search;

/**
 * Directory groups search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Groups implements Search
{

        /**
         * The user principal name.
         * @var string 
         */
        private $_principal;
        /**
         * The attributes filter.
         * @var array 
         */
        private $_filter;

        /**
         * Constructor.
         * @param string $principal The user principal name.
         * @param array $attributes The attribute filter.
         */
        public function __construct($principal, $attributes = null)
        {
                if (empty($attributes)) {
                        $attributes = array(Group::ATTR_NAME);
                }

                $this->_principal = $principal;
                $this->_filter = $attributes;
        }

        /**
         * Set attributes filter.
         * @param array $attributes The returned attributes.
         */
        public function setFilter($attributes)
        {
                $this->_filter = $attributes;
        }

        /**
         * Set user principal.
         * @param string $principal The user principal name.
         */
        public function setPrincipal($principal)
        {
                $this->_principal = $principal;
        }

        /**
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return array
         */
        public function getResult($manager)
        {
                $domain = $manager->getRealm($this->_principal);
                $result = array();

                foreach ($manager->getServices($domain) as $name => $service) {
                        if (($groups = $this->getGroups($manager, $service, $name)) != null) {
                                $result = array_merge($result, $groups);
                        }
                }

                return $result;
        }

        /**
         * Get directory groups.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getGroups($manager, $service, $name)
        {
                try {
                        return $service->getGroups($this->_principal, $this->_filter);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
