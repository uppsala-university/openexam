<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
