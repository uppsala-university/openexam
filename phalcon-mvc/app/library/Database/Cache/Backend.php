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
use OpenExam\Library\Database\Cache\Result\Coherence;
use OpenExam\Library\Database\Cache\Result\Entry;
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
 * The TTL for table index should always be > than the TTL for result set 
 * cache or we might suffer from not result set that is never invalidated on
 * insert(), update() or delete() because they are missing in the table index
 * but still found by hashed queries.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Backend
{

        /**
         * Cleanup index interval.
         */
        const HOUSEKEEP_INTERVAL = 900;

        /**
         * The real cache backend.
         * @var BackendInterface 
         */
        private $_cache;
        /**
         * Time to live for cache entries (query result set).
         * @var int 
         */
        private $_ttlres;
        /**
         * Time to live for cache entry index (the table set index).
         * @var int 
         */
        private $_ttlidx;
        /**
         * Conflict resolve mode.
         * @var Coherence 
         */
        private $_coherence;

        /**
         * Constructor.
         * 
         * @param BackendInterface $cache The real cache backend.
         * @param int $resolve How to resolve cache conflicts (Coherence::ON_CONFLICT_XXX or 0).
         */
        public function __construct($cache, $resolve = 0)
        {
                $this->_cache = $cache;

                $this->_ttlres = $cache->getFrontend()->getLifetime();
                $this->_ttlidx = $cache->getFrontend()->getLifetime() + 86400;

                $this->_coherence = new Coherence($this->_cache, $this->_ttlres, $this->_ttlidx);
        }

        /**
         * Set conflict resolve mode.
         * 
         * Use either one of the Coherence::ON_CONFLICT_XXX constants. Use 
         * ON_CONFLICT_IGNORE to disable cache conflict resolution.
         * 
         * @param int $mode The conflict resolve mode.
         */
        public function setResolveMode($mode)
        {
                $this->_coherence->setResolveMode($mode);
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
                if (($data = $this->_cache->get($table, $this->_ttlidx)) == null) {
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
                return $this->_cache->exists($keyName, $this->_ttlres);
        }

        /**
         * Get cache data.
         * @param string $keyName The cache key.
         * @return mixed
         */
        public function get($keyName)
        {
                // 
                // Return null if cache entry is missing:
                // 
                if (($entry = $this->_cache->get($keyName, $this->_ttlres)) == null) {
                        return null;
                }

                // 
                // Fixup cache entry if requested and needed. Return content
                // if entry is valid:
                // 
                if ($this->_coherence->resolve($entry)) {
                        return $entry->content;
                } else {
                        return null;
                }
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
                $entry = new Entry($keyName);
                $entry->setContent($content);
                $entry->setTables($tables);
                $entry->save($this->_cache, $this->_ttlres);

                // 
                // Update cache key index:
                // 
                foreach ($tables as $table) {
                        $active = array();
                        $remove = array();
                        $insert = false;

                        // 
                        // Find active and expired result sets:
                        // 
                        if ($this->housekeep($table, $active, $remove)) {
                                $insert = true;
                        } elseif ($this->_cache->exists($table, $this->_ttlidx)) {
                                $active = $this->_cache->get($table, $this->_ttlidx);
                        }

                        // 
                        // Update if result set has expired:
                        // 
                        if (count($remove) > 0) {
                                $insert = true;
                        }

                        // 
                        // Update if key is missing in table index:
                        // 
                        if (!in_array($keyName, $active)) {
                                $active[] = $keyName;
                                $insert = true;
                        }

                        // 
                        // Check whether table index should be updated:
                        // 
                        if ($insert) {
                                $this->_cache->save($table, $active, $this->_ttlidx);
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
                if (!$this->_cache->exists($table, $this->_ttlidx)) {
                        return false;
                }
                if (!($data = $this->_cache->get($table, $this->_ttlidx))) {
                        return false;
                }

                // 
                // Delete matching entries in cache:
                // 
                foreach ($data as $keyName) {
                        if (($result = $this->_cache->get($keyName, $this->_ttlres))) {
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
         * Table index maintenance.
         * 
         * Cleanup expired result set keys from table index. Returns true if
         * housekeeping where performed. The remaining (and possibly active) 
         * results sets are returned by reference.
         * 
         * The cleanup is potential costly, so its only run at periodical 
         * interval.
         * 
         * @param string $table The table name.
         * @param array $active The remaining result sets.
         * @param array $remove The removed result sets.
         * @return boolean
         */
        private function housekeep($table, &$active, &$remove)
        {
                // 
                // Make sure we are dealing with arrays:
                // 
                if (!is_array($active)) {
                        $active = array();
                }
                if (!is_array($remove)) {
                        $remove = array();
                }

                // 
                // Don't continue if housekeep key exist:
                // 
                if ($this->_cache->exists(sprintf("%s-housekeep", $table), self::HOUSEKEEP_INTERVAL)) {
                        return false;
                }

                // 
                // Get existing result set:
                // 
                if ($this->_cache->exists($table, $this->_ttlidx)) {
                        $exists = $this->_cache->get($table, $this->_ttlidx);
                } else {
                        $exists = array();
                }

                // 
                // Find expired result set:
                // 
                foreach ($exists as $res) {
                        if (!$this->_cache->exists($res, $this->_ttlres)) {
                                $remove[] = $res;
                        } else {
                                $active[] = $res;
                        }
                }

                // 
                // Set housekeep locker key:
                // 
                $this->_cache->save(sprintf("%s-housekeep", $table), (int) time(), self::HOUSEKEEP_INTERVAL);

                // 
                // This table should be housekeeped:
                // 
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
