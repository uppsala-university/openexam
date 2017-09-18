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

namespace OpenExam\Library\Database\Cache\Backend;

use OpenExam\Library\Core\Synchronize\Mutex;
use OpenExam\Library\Database\Cache\Result\Coherence;
use OpenExam\Library\Database\Cache\Result\Entry;
use Phalcon\Cache\BackendInterface;

/**
 * The indexed cache backend.
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
class Indexed implements CacheBackend
{

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
         * The cache conflict resolver.
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
                $this->_ttlidx = $cache->getFrontend()->getLifetime() + 604800;
        }

        /**
         * Set cache coherence options.
         * @param array $options The cache coherence options.
         */
        public function setCoherence($options)
        {
                $this->_coherence = new Coherence($this->_cache, $this->_ttlres, $this->_ttlidx);
                $this->_coherence->setOptions($options);
        }

        /**
         * Delete table cache.
         * 
         * Calling this method will delete all cached result set related to
         * table as well as the cache key index for named table.
         * 
         * @param string|array $table The table name.
         * @return boolean
         */
        public function delete($table)
        {
                // 
                // Use mutex lock on table:
                // 
                $mutex = new Mutex($this->_cache);

                // 
                // Single call if operating on a one table:
                // 
                if (is_string($table)) {
                        return $mutex->open($table, function() use($table) {
                                    return $this->cleanup($table);
                            });
                }

                // 
                // The return status variable:
                // 
                $status = true;

                // 
                // Process all tables. Set status to false if any fails.
                // 
                foreach ($table as $t) {
                        if ($mutex->open($table, function() use($t) {
                                    return $this->cleanup($t);
                            }) == false) {
                                $status = false;
                        }
                }

                // 
                // Return cleanup status:
                // 
                return $status;
        }

        /**
         * Check if cache key exists.
         * 
         * @param string $keyName The cache key.
         * @return boolean
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
                // Simply return entry:
                // 
                if (!isset($this->_coherence)) {
                        return $entry;
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
                // Use mutex lock on table:
                // 
                $mutex = new Mutex($this->_cache);

                // 
                // We don't need the extra functionality of the cache  
                // entry class if not dealing with cache coherence.
                // 
                if (isset($this->_coherence)) {
                        $entry = new Entry($keyName);
                        $entry->setContent($content);
                        $entry->setTables($tables);
                        $entry->save($this->_cache, $this->_ttlres);
                } else {
                        $this->_cache->save($keyName, $content, $this->_ttlres);
                }

                // 
                // Update cache key index:
                // 
                foreach ($tables as $table) {
                        if (!$this->update($mutex, $keyName, $table)) {
                                return;
                        }
                }
        }

        /**
         * Update table index.
         * 
         * Insert $keyName in table index. Attempt to lock the index using
         * mutex before updating. If locking fails, then the cache key is
         * deleted and false is returned.
         * 
         * @param Mutex $mutex The mutex for locking cache.
         * @param string $keyName The cache key.
         * @param string $tables The affected table.
         * @return boolean
         */
        private function update($mutex, $keyName, $table)
        {
                // 
                // Remove cache key if failing to acquire mutex lock
                // on any one of the affected tables. We do this to
                // prevent dangling result set keys not present in the
                // table index -> not invalidated.
                // 
                if (!$mutex->acquire($table)) {
                        $this->_cache->delete($keyName);
                        return false;
                }

                $active = array();
                $remove = array();

                // 
                // Find active and expired result sets:
                // 
                if (isset($this->_coherence)) {
                        $insert = $this->_coherence->housekeep($table, $active, $remove);
                } else {
                        $insert = false;
                }

                // 
                // Get currently active table indexes:
                // 
                if ($insert === false) {
                        $active = $this->_cache->get($table, $this->_ttlidx);
                }
                if (is_null($active)) {
                        $active = array();
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

                // 
                // Release mutex lock:
                // 
                $mutex->release();

                // 
                // The table index update was successful.
                return true;
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
         * @param boolean $scan Scan for match in all result set keys.
         * @return boolean
         */
        public function invalidate($table, $id, $scan = true)
        {
                // 
                // Get all result set keys if scan mode is used:
                // 
                if ($scan) {
                        $data = array();
                        $keys = $this->_cache->queryKeys();

                        $prefix = $this->_cache->getOptions()['prefix'];
                        $length = strlen($prefix);

                        foreach ($keys as $full) {
                                $key = substr($full, $length);
                                if (is_numeric($key)) {
                                        array_push($data, $key);
                                }
                        }
                } else {
                        // 
                        // Nothing to do if index is missing:
                        // 
                        if (!$this->_cache->exists($table, $this->_ttlidx)) {
                                return false;
                        }
                        if (!($data = $this->_cache->get($table, $this->_ttlidx))) {
                                return false;
                        }
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
         * Cleanup table index cache.
         * 
         * @param string $table The table name.
         * @return boolean
         */
        private function cleanup($table)
        {
                // 
                // The cleanup status:
                // 
                $status = true;

                // 
                // Nothing to do if index is missing:
                // 
                if (($data = $this->_cache->get($table, $this->_ttlidx)) == null) {
                        return false;
                }

                // 
                // Delete the cache key index itself:
                // 
                if ($this->_cache->delete($table) == false) {
                        $status = false;
                }

                // 
                // Delete all cached result set:
                // 
                foreach ($data as $keyName) {
                        if ($this->_cache->delete($keyName) == false) {
                                $status = false;
                        }
                }

                // 
                // Return complete cleanup status:
                // 
                return $status;
        }

}
