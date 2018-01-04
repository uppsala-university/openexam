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
// File:    Native.php
// Created: 2017-09-14 12:37:41
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Backend;

use Phalcon\Cache\BackendInterface;

/**
 * The native cache backend.
 * 
 * Provides a thin wrapper for the cache backend. The TTL is by default 
 * the same as the supplied cache object, but can be set explicit if
 * wanted. 
 * 
 * Unless using the simple mediator and the database cache is going to be
 * used to store long term queries, its best to leave TTL as is.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Native implements CacheBackend
{

        /**
         * The real cache backend.
         * @var BackendInterface 
         */
        private $_cache;
        /**
         * The TTL value.
         * @var int 
         */
        private $_ttl;

        /**
         * Constructor.
         * 
         * @param BackendInterface $cache The real cache backend.
         */
        public function __construct($cache)
        {
                $this->_cache = $cache;
                $this->_ttl = $cache->getFrontend()->getLifetime();
        }

        /**
         * Set TTL for cache entries.
         * @param int $ttl The TTL in seconds.
         */
        public function setLifetime($ttl)
        {
                $this->ttl = $ttl;
        }

        /**
         * Set cache coherence options.
         * @param array $options The cache coherence options.
         */
        public function setCoherence($options)
        {
                // ignore
        }

        /**
         * Delete cached entries.
         * 
         * Call with an array to delete multiple keys at once.
         * 
         * @param string|array $keyName The key name.
         * @return boolean
         */
        public function delete($keyName)
        {
                $this->_cache->delete($keyName);
        }

        /**
         * Check if cache key exists.
         * 
         * @param string $keyName The cache key.
         * @return boolean
         */
        public function exists($keyName)
        {
                return $this->_cache->exists($keyName, $this->_ttl);
        }

        /**
         * Get cache data.
         * @param string $keyName The cache key.
         * @return mixed
         */
        public function get($keyName)
        {
                return $this->_cache->get($keyName, $this->_ttl);
        }

        /**
         * Invalidate cache entries.
         * 
         * Don't make sense for native cache backend. Calling this method will
         * always return true.
         * 
         * @param string $table The table name.
         * @param int $id The primary key ID.
         * @param boolean $scan Scan for match in all result set keys.
         * @return boolean
         */
        public function invalidate($table, $id, $scan = true)
        {
                return true;
        }

        /**
         * Save result set in cache.
         * 
         * Call this method to store the result set in cache key. The tables
         * entry contains all tables affected by the cached content.
         * 
         * The tables argument is optional and ignored for native cache
         * backends.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The content to store in cache.
         * @param array $tables The affected tables.
         */
        public function save($keyName, $content, $tables)
        {
                $this->_cache->save($keyName, $content, $this->_ttl);
        }

}
