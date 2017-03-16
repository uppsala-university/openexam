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

use OpenExam\Library\Core\Cache\Backend\Xcache as XcacheBackend;
use OpenExam\Library\Database\Exception;
use Phalcon\Cache\Backend\Aerospike as AerospikeBackend;
use Phalcon\Cache\Backend\Apc as ApcBackend;
use Phalcon\Cache\Backend\Database as DatabaseBackend;
use Phalcon\Cache\Backend\File as FileBackend;
use Phalcon\Cache\Backend\Libmemcached as LibMemcachedBackend;
use Phalcon\Cache\Backend\Memcache as MemcacheBackend;
use Phalcon\Cache\Backend\Mongo as MongoBackend;
use Phalcon\Cache\Backend\Redis as RedisBackend;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\FrontendInterface;

/**
 * The cache backend.
 * 
 * Each database table has an index (i.e. isolated-adapter-cache-exams) that
 * contains an array of cache keys containing result sets related to that 
 * table. The index is modified upon calling delete() or save(). 
 * 
 * Calling delete() for an table is potential costly as it invalidates all
 * result sets cached for that table based on its index.
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
         * @param array $tables The affected tables.
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

        /**
         * Invalidate cache entries.
         * 
         * Use this method to invalidate all cache entries on $table 
         * with $id as primary key. Similar to delete() but more selective 
         * and intended to be used in response to a cache hit miss.
         * 
         * @param string $table The table name.
         * @param int $id The primary key ID.
         * @return boolean
         */
        public function invalidate($table, $id)
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
                // Delete matching entries in cache:
                // 
                foreach ($data as $keyName) {
                        if (($result = $this->_cache->get($keyName))) {
                                if ($result->numRows() == 0) {
                                        continue;
                                } elseif (!($record = $result->fetch())) {
                                        continue;
                                } elseif (!isset($record['id'])) {
                                        continue;
                                } elseif ($record['id'] == $id) {
                                        $this->_cache->delete($keyName);
                                }
                        }
                }

                return true;
        }

        /**
         * Create cache backend.
         * 
         * @param string $type The cache type.
         * @param FrontendInterface $frontend The cache frontend.
         * @param array $options Backend options.
         * @return BackendInterface
         * @throws Exception
         */
        public static function create($type, $frontend, $options)
        {
                switch ($type) {
                        case 'xcache':
                                return new XcacheBackend($frontend, $options);
                        case 'apc':
                                return new ApcBackend($frontend, $options);
                        case 'redis':
                                return new RedisBackend($frontend, $options);
                        case 'memcache':
                                return new MemcacheBackend($frontend, $options);
                        case 'libmemcached':
                                return new LibMemcachedBackend($frontend, $options);
                        case 'mongo':
                                return new MongoBackend($frontend, $options);
                        case 'aerospike':
                                return new AerospikeBackend($frontend, $options);
                        case 'database':
                                return new DatabaseBackend($frontend, $options);
                        case 'file':
                                return new FileBackend($frontend, $options);
                        default:
                                throw new Exception("Unsupported cache backend $type");
                }
        }

}
