<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    BackendInterface.php
// Created: 2017-09-14 15:19:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Backend;

/**
 * The cache backend interface.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface CacheBackend
{

        /**
         * Delete cached entries.
         * 
         * Call with an array to delete multiple keys at once.
         * 
         * @param string|array $keyName The key name.
         * @return boolean
         */
        public function delete($keyName);

        /**
         * Check if cache key exists.
         * 
         * @param string $keyName The cache key.
         * @return boolean
         */
        public function exists($keyName);

        /**
         * Get cache data.
         * @param string $keyName The cache key.
         * @return mixed
         */
        public function get($keyName);

        /**
         * Save result set in cache.
         * 
         * Call this method to store the result set in cache key. The tables
         * entry contains all tables affected by the cached content.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The content to store in cache.
         * @param array $tables The affected tables.
         */
        public function save($keyName, $content, $tables);

        /**
         * Invalidate cache entries.
         * 
         * Calling this method should invalidate all cache entries related
         * to the primary ID on named table. Cache backend are free to ignore
         * implementing this method if invalidation don't make sense.
         * 
         * @param string $table The table name.
         * @param int $id The primary key ID.
         * @param boolean $scan Scan for match in all result set keys.
         * @return boolean
         */
        public function invalidate($table, $id, $scan = true);
}
