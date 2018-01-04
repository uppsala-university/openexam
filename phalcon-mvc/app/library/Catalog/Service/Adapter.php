<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    Adapter.php
// Created: 2014-10-22 04:17:43
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Service;

use OpenExam\Library\Catalog\DirectoryService;
use Phalcon\Mvc\User\Component;

/**
 * Directory service adapter.
 * 
 * Classes implementing the DirectoryService interface should derive from 
 * this class and override any methods from this class to provide required 
 * functionality. This class provides dummy fallback methods.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class Adapter extends Component implements DirectoryService
{

        /**
         * The service name.
         * @var string 
         */
        protected $_name;
        /**
         * The service type.
         * @var string 
         */
        protected $_type;
        /**
         * Set caching lifetime. 0 to disable.
         * @var long 
         */
        protected $_lifetime = 0;
        /**
         * The search domain.
         * @var string 
         */
        protected $_domain;
        /**
         * The attribute filter.
         * @var array 
         */
        protected $_filter;

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_lifetime);

                unset($this->_name);
                unset($this->_type);

                unset($this->_domain);
                unset($this->_filter);
        }

        /**
         * Set service name.
         * @param string $name The service name.
         */
        public function setName($name)
        {
                $this->_name = $name;
        }

        /**
         * Get service name.
         * @return string
         */
        public function getServiceName()
        {
                return $this->_name;
        }

        /**
         * Set caching lifetime (0 to disable).
         * @param long $lifetime The caching lifetime.
         */
        public function setCacheLifetime($lifetime)
        {
                $this->_lifetime = $lifetime;
        }

        /**
         * Update cached entry and return passed in data.
         * 
         * This method will update cache if:
         * 1. Using cache is enabled (lifetime != 0)
         * 2. The cache key don't exists.
         * 
         * @param string $key The cache key.
         * @param mixed $result The result data.
         * @return mixed
         */
        protected function setCacheData($key, $result)
        {
                // 
                // Check if this service has disabled cache:
                // 
                if ($this->_lifetime == 0) {
                        return $result;
                }

                // 
                // Don't cache nulls:
                // 
                if (!isset($result)) {
                        return $result;
                }

                // 
                // Require cache key (obvious):
                // 
                if (!isset($key)) {
                        return $result;
                }

                // 
                // Don't cache if valid content exists:
                // 
                if ($this->cache->exists($key, $this->_lifetime)) {
                        return $result;
                }

                // 
                // Ensure that strings are serialized!
                // 
                if (is_string($result)) {
                        $this->cache->save($key, serialize($result), $this->_lifetime);
                } else {
                        $this->cache->save($key, $result, $this->_lifetime);
                }

                return $result;
        }

        public function getGroups($principal, $attributes = null)
        {
                return null;
        }

        public function getMembers($group, $domain = null, $attributes = null)
        {
                return null;
        }

        public function getAttributes($attribute, $principal = null)
        {
                return null;
        }

        public function getAttribute($attribute, $principal = null)
        {
                return null;
        }

        public function getPrincipals($needle, $search = null, $options = null)
        {
                return null;
        }

        public function getPrincipal($needle, $search = null, $domain = null, $attr = null)
        {
                return null;
        }

        public function getDomains()
        {
                return null;
        }

        /**
         * Set user domain.
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
         * Get user domain.
         * @return string
         */
        public function getDomain()
        {
                return $this->_domain;
        }

        /**
         * Get attributes filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_filter;
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        abstract public function getConnection();
}
