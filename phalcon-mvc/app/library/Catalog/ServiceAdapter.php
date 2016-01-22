<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceAdapter.php
// Created: 2014-10-22 04:17:43
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

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
abstract class ServiceAdapter extends Component implements DirectoryService
{

        /**
         * The service name.
         * @var string 
         */
        protected $name;
        /**
         * The service type.
         * @var string 
         */
        protected $type;
        /**
         * Set caching lifetime. 0 to disable.
         * @var long 
         */
        protected $lifetime = 0;

        /**
         * Set service name.
         * @param string $name The service name.
         */
        public function setName($name)
        {
                $this->name = $name;
        }

        /**
         * Set caching lifetime (0 to disable).
         * @param long $lifetime The caching lifetime.
         */
        public function setCacheLifetime($lifetime)
        {
                $this->lifetime = $lifetime;
        }

        /**
         * Update cached entry and return passed in data.
         * @param string $key The cache key.
         * @param array $result The result data.
         * @return array
         */
        protected function setCacheData($key, $result)
        {
                if ($this->lifetime == 0) {
                        return $result;
                }
                
                $this->cache->save($key, $result, $this->lifetime);
                return $result;
        }

        public function getGroups($principal, $attributes)
        {
                return null;
        }

        public function getMembers($group, $domain, $attributes)
        {
                return null;
        }

        public function getAttribute($principal, $attr)
        {
                return null;
        }

        public function getPrincipal($needle, $search, $options)
        {
                return null;
        }

        /**
         * Get service connection.
         * @return ServiceConnection
         */
        public function getConnection()
        {
                return null;
        }

}
