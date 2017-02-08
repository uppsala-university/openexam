<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Backend.php
// Created: 2017-01-16 22:26:35
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache;

use Phalcon\Cache\BackendInterface;

/**
 * The cache backend.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Backend
{

        /**
         * The real cache backend.
         * @var BackendInterface 
         */
        private $_cache;

        /**
         * Constructor.
         * @param BackendInterface $cache The real cache backend.
         */
        public function __construct($cache)
        {
                $this->_cache = $cache;
        }

        /**
         * Delete table cache.
         * 
         * Calling this method will delete all cached result set related to
         * table as well as the cache key index for named table.
         * 
         * @param string $table The table name.
         */
        public function delete($table)
        {
                // 
                // Nothing to do if index is missing:
                // 
                if (!$this->_cache->exists($table)) {
                        return false;
                }
                if (!($data = $this->_cache->get($table))) {
                        return false;
                }

                // 
                // Delete all cached result set:
                // 
                foreach ($data as $keyName) {
                        $this->_cache->delete($keyName);
                }

                // 
                // Delete the cache key index itself:
                // 
                return $this->_cache->delete($table);
        }

        /**
         * Check if cache key exists.
         * @param string $keyName The cache key.
         */
        public function exists($keyName)
        {
                return $this->_cache->exists($keyName);
        }

        /**
         * Get cache data.
         * @param string $keyName The cache key.
         */
        public function get($keyName)
        {
                return $this->_cache->get($keyName);
        }

        /**
         * Save result set in cache.
         * 
         * Call this method to store the result set in cache key. This method
         * will also update the cache key index for named tables.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The content to store in cache.
         * @param string $tables The table name.
         */
        public function save($keyName, $content, $tables)
        {
                // 
                // Save result set:
                // 
                $this->_cache->save($keyName, $content);

                // 
                // Update cache key index:
                // 
                foreach ($tables as $table) {
                        if ($this->_cache->exists($table)) {
                                $data = $this->_cache->get($table);
                        }
                        if (!isset($data)) {
                                $data = array();
                        }
                        if (!in_array($keyName, $data)) {
                                $data[] = $keyName;
                                $this->_cache->save($table, $data);
                        }
                }
        }

}
