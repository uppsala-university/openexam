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
// File:    Distributed.php
// Created: 2017-01-31 23:31:26
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache\Strategy;

use Phalcon\Cache\Backend;
use Phalcon\Cache\BackendInterface;

/**
 * Network distributed cache.
 * 
 * Provide a two level cache where data is fetched from lowest cache, but 
 * invalidated when changed in upper cache. This class only makes sense when
 * running multiple web frontends.
 * 
 * The upper level is a shared cache (i.e. memcached) accessed over network. 
 * The lower cache is system local cache (i.e. apc). A bad analogous to this 
 * class is the RAM cache in a SMP computer system.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Distributed extends Backend implements BackendInterface
{

        /**
         * The upper cache.
         * @var BackendInterface 
         */
        private $_upper;
        /**
         * The local cache.
         * @var BackendInterface 
         */
        private $_lower;

        /**
         * Set upper cache backend (usually network shared).
         * @param BackendInterface $backend The cache backend.
         */
        public function setUpperBackend($backend)
        {
                $this->_upper = $backend;
        }

        /**
         * Set lower cache backend (usually system local).
         * @param BackendInterface $backend The cache backend.
         */
        public function setLowerBackend($backend)
        {
                $this->_lower = $backend;
        }

        /**
         * Get lower cache backend.
         * @return BackendInterface
         */
        public function getLowerBackend()
        {
                return $this->_lower;
        }

        /**
         * Get upper cache backend.
         * @return BackendInterface
         */
        public function getUpperBackend()
        {
                return $this->_upper;
        }

        /**
         * Returns a cached content
         *
         * @param int|string $keyName 
         * @param int $lifetime 
         * @return mixed 
         */
        public function get($keyName, $lifetime = null)
        {
                if (!$this->_upper->exists($keyName, $lifetime)) {
                        return false;
                }

                if ($this->_lower->exists($keyName, $lifetime)) {
                        return $this->_lower->get($keyName, $lifetime);
                }

                $content = $this->_upper->get($keyName, $lifetime);
                $this->_lower->save($keyName, $content, $lifetime);

                return $content;
        }

        /**
         * Stores cached content into the file backend and stops the frontend
         *
         * @param int|string $keyName 
         * @param string $content 
         * @param int $lifetime 
         * @param boolean $stopBuffer 
         */
        public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
        {
                $this->_upper->save($keyName, $content, $lifetime, $stopBuffer);
                $this->_lower->save($keyName, $content, $lifetime, $stopBuffer);
        }

        /**
         * Deletes a value from the cache by its key
         *
         * @param int|string $keyName 
         * @return boolean 
         */
        public function delete($keyName)
        {
                return $this->_upper->delete($keyName) && $this->_lower->delete($keyName);
        }

        /**
         * Checks if cache exists and it hasn't expired
         *
         * @param string $keyName 
         * @param int $lifetime 
         * @return boolean 
         */
        public function exists($keyName = null, $lifetime = null)
        {
                if ($this->_upper->exists($keyName, $lifetime)) {
                        return true;
                }

                $this->_lower->delete($keyName);
                return false;
        }

        /**
         * Query the existing cached keys
         *
         * @param string $prefix 
         * @return array 
         */
        public function queryKeys($prefix = null)
        {
                return $this->_upper->queryKeys($prefix);
        }

}
