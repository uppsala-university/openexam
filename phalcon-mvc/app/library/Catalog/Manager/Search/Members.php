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
// File:    Members.php
// Created: 2017-04-11 22:59:40
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Manager\Search;
use OpenExam\Library\Catalog\Principal as UserPrincipal;

/**
 * Directory members search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Members implements Search
{

        /**
         * The group name.
         * @var string 
         */
        private $_group;
        /**
         * The search domain.
         * @var string 
         */
        private $_domain;
        /**
         * The attribute filter.
         * @var array 
         */
        private $_filter;

        /**
         * Constructor.
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         */
        public function __construct($group, $domain = null, $attributes = null)
        {
                if (empty($attributes)) {
                        $attributes = array(UserPrincipal::ATTR_PN, UserPrincipal::ATTR_NAME, UserPrincipal::ATTR_MAIL);
                }

                $this->_group = $group;
                $this->_domain = $domain;
                $this->_filter = $attributes;
        }

        /**
         * Set group name.
         * @param string $group The group name.
         */
        public function setGroup($group)
        {
                $this->_group = $group;
        }

        /**
         * Set search domain.
         * @param string $domain The search domain.
         */
        public function setDomain($domain)
        {
                $this->_domain = $domain;
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
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return array
         */
        public function getResult($manager)
        {
                $result = array();

                foreach ($manager->getServices($this->_domain) as $name => $service) {
                        if (($members = $this->getMembers($manager, $service, $name)) != null) {
                                $result = array_merge($result, $members);
                        }
                }

                return $result;
        }

        /**
         * Get directory members.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getMembers($manager, $service, $name)
        {
                try {
                        return $service->getMembers($this->_group, $this->_domain, $this->_filter);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
