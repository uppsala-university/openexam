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
// File:    HashKeyValue.php
// Created: 2017-03-28 14:34:42
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Cache\Backend;

use Phalcon\Cache\BackendInterface;

/**
 * Hash key/value cache backend.
 * 
 * Provides a hashed key/value store with a cache backend as fallback. Use
 * this class as an intermediate store of values fetched from the backend
 * cache. The values are delivered as is from this store if found, thus
 * by-passing the usual serialization by frontends.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class KeyValueStore
{

        /**
         * The backend cache.
         * @var BackendInterface 
         */
        private $_backend;
        /**
         * The key/value store.
         * @var array 
         */
        private $_store = array();

        /**
         * Constructor.
         * 
         * @param BackendInterface $backend The backend cache.
         */
        public function __construct($backend)
        {
                $this->_backend = $backend;
        }

        /**
         * Delete key from cache.
         * 
         * @param string $keyName The key name.
         */
        public function delete($keyName)
        {
                if (isset($this->_store[$keyName])) {
                        unset($this->_store[$keyName]);
                }
                $this->_backend->delete($keyName);
        }

        /**
         * Check if key exists.
         * 
         * @param string $keyName The key name.
         * @param type $lifetime
         * @return boolean
         */
        public function exists($keyName = null, $lifetime = null)
        {
                if (isset($this->_store[$keyName])) {
                        return true;
                } else {
                        return $this->_backend->exists($keyName, $lifetime);
                }
        }

        /**
         * Get key value.
         * 
         * @param string $keyName The key name.
         * @param int $lifetime The TTL for the key.
         * @return mixed|null
         */
        public function get($keyName, $lifetime = null)
        {
                if (isset($this->_store[$keyName])) {
                        return $this->_store[$keyName];
                }

                $content = $this->_backend->get($keyName, $lifetime);
                $this->_store[$keyName] = $content;

                return $content;
        }

        /**
         * Save cache content.
         * 
         * @param string $keyName The key name.
         * @param string $content The content value.
         * @param int $lifetime The TTL for the key.
         * @param bool $stopBuffer Stop frontend buffer.
         */
        public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
        {
                $this->_backend->save($keyName, $content, $lifetime, $stopBuffer);
                $this->store($keyName, $content);
        }

        /**
         * Query all keys.
         * 
         * @param string $prefix The key prefix (optional).
         * @return array
         */
        public function queryKeys($prefix = null)
        {
                if (isset($prefix)) {
                        $length = strlen($prefix);
                        return array_filter(array_keys($this->_store), function($key) use($length, $prefix) {
                                return strncmp($key, $prefix, $length) == 0;
                        });
                } else {
                        return array_keys($this->_store);
                }
        }

        /**
         * Get all cache backends.
         * @return BackendInterface[]
         */
        public function getBackends()
        {
                return $this->_backend->getBackends();
        }

        /**
         * Store content in key/value store.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The key value.
         */
        private function store($keyName, $content)
        {
                if ($content === serialize(false)) {
                        $this->_store[$keyName] = false;
                } elseif (!is_string($content)) {
                        $this->_store[$keyName] = $content;
                } elseif (($string = unserialize($content))) {
                        $this->_store[$keyName] = $string;
                } elseif (is_null($string)) {
                        $this->_store[$keyName] = null;
                } else {
                        $this->_store[$keyName] = $content;
                }
        }

}
