<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Coherence.php
// Created: 2017-03-27 22:34:23
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache\Result;

use OpenExam\Library\Database\Exception;
use Phalcon\Cache\BackendInterface;

/**
 * Coherence for result set cache entries.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Coherence
{

        /**
         * Cleanup index interval.
         */
        const HOUSEKEEP_INTERVAL = 900;
        /**
         * Ignore conflict in cache consistence.
         */
        const ON_CONFLICT_IGNORE = 0;
        /**
         * Resolve cache conflict by re-adding result set.
         */
        const ON_CONFLICT_READD = 1;
        /**
         * Resolve cache conflict by restore result set and table indexes.
         */
        const ON_CONCLICT_RESTORE = 2;
        /**
         * Resolve cache conflict by deleting result set.
         */
        const ON_CONFLICT_DELETE = 3;
        /**
         * Resolve cache conflict by deleting result set from table indexes.
         */
        const ON_CONFLICT_CLEAN = 4;
        /**
         * Resolve cache conflict by deleting result set and table indexes.
         */
        const ON_CONFLICT_PURGE = 5;

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
         * @var int 
         */
        private $_resolve = self::ON_CONFLICT_DELETE;
        /**
         * The housekeep interval.
         * @var int 
         */
        private $_interval = self::HOUSEKEEP_INTERVAL;

        /**
         * Constructor.
         * 
         * @param BackendInterface $cache The cache backend.
         * @param int $ttlres The TTL for result set.
         * @param int $ttlidx The TTL for table indexes.
         */
        public function __construct($cache, $ttlres, $ttlidx)
        {
                $this->_cache = $cache;

                $this->_ttlres = $ttlres;
                $this->_ttlidx = $ttlidx;
        }

        /**
         * Check result set cache entry.
         * 
         * @param Entry $entry The cache entry.
         * @return boolean
         */
        public function check($entry)
        {
                if ($this->_resolve == 0) {
                        return true;
                } else {
                        return $entry->validate($this->_cache, $this->_ttlres, $this->_ttlidx);
                }
        }

        /**
         * Resolve result set cache entry conflict.
         * 
         * @param Entry $entry The cache entry.
         * @return boolean
         */
        public function resolve($entry)
        {
                if ($this->_resolve == 0) {
                        return true;
                }

                // 
                // Perform entry validation if not yet done:
                // 
                if ($entry->isChecked() == false) {
                        $this->check($entry);
                }
                if ($entry->isInvalid() == false) {
                        return true;    // Nothing to do
                }

                // 
                // Requires table indexes:
                // 
                if (!is_array($entry->tables)) {
                        throw new Exception("Table indexes is missing");
                }

                // 
                // Resolve conflict using prefered strategy:
                // 
                switch ($this->_resolve) {
                        case self::ON_CONFLICT_READD:
                                return $this->read($entry);
                        case self::ON_CONCLICT_RESTORE:
                                return $this->restore($entry);
                        case self::ON_CONFLICT_DELETE:
                                return $this->delete($entry);
                        case self::ON_CONFLICT_CLEAN:
                                return $this->clean($entry);
                        case self::ON_CONFLICT_PURGE:
                                return $this->purge($entry);
                }

                return $entry->isInvalid();
        }

        /**
         * Use re-add strategy to resolve conflict.
         * @param Entry $entry The cache entry.
         */
        private function read($entry)
        {
                // 
                // Insert result set cache entry:
                // 
                if ($entry->isMissing()) {
                        $entry->save($this->_cache, $this->_ttlres);
                }
        }

        /**
         * Use restore strategy to resolve conflict.
         * @param Entry $entry The cache entry.
         */
        private function restore($entry)
        {
                // 
                // Insert result set cache entry:
                // 
                if ($this->_missing) {
                        $entry->save($this->_cache, $this->_ttlres);
                }

                // 
                // Update table indexes if required:
                // 
                foreach ($this->_tables as $table) {
                        if (!$this->_cache->exists($table, $this->_ttlidx)) {
                                $this->_cache->save($table, array($entry->ckey), $this->_ttlidx);
                        } elseif (($data = $this->_cache->get($table, $this->_ttlidx)) !== null) {
                                if (!in_array($entry->ckey, $data)) {
                                        $data[] = $entry->ckey;
                                        $this->_cache->save($table, $data, $this->_ttlidx);
                                }
                        }
                }

                // 
                // The cache entry and related table indexes has been updated.
                // 
                $entry->setResolved();
        }

        /**
         * Use delete strategy to resolve conflict.
         * @param Entry $entry The cache entry.
         */
        private function delete($entry)
        {
                // 
                // Delete existing result set cache entry:
                // 
                if (!$entry->isMissing()) {
                        $entry->delete($this->_cache);
                }
        }

        /**
         * Use clean strategy to resolve conflict.
         * @param Entry $entry The cache entry.
         */
        private function clean($entry)
        {
                // 
                // Delete existing result set cache entry:
                // 
                if (!$entry->isMissing()) {
                        $entry->delete($this->_cache);
                }

                // 
                // Update table indexes if required:
                // 
                foreach ($entry->tables as $table) {
                        if (!$this->_cache->exists($table, $this->_ttlidx)) {
                                continue;
                        } elseif (($data = $this->_cache->get($table, $this->_ttlidx)) !== null) {
                                if (in_array($entry->ckey, $data)) {
                                        $key = array_search($entry->ckey, $data);
                                        unset($data[$key]);
                                        $this->_cache->save($table, $data, $this->_ttlidx);
                                }
                        }
                }
        }

        /**
         * Use purge strategy to resolve conflict.
         * @param Entry $entry The cache entry.
         */
        private function purge($entry)
        {
                // 
                // Delete existing result set cache entry:
                // 
                if ($entry->isMissing()) {
                        $entry->delete($this->_cache);
                }

                // 
                // Update table indexes if required:
                // 
                foreach ($entry->tables as $table) {
                        if ($this->_cache->exists($table, $this->_ttlidx)) {
                                $this->_cache->delete($table);
                        }
                }
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
        public function housekeep($table, &$active, &$remove)
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
                // Don't continue if housekeep key exist or if housekeeping
                // has been disabled.
                // 
                if ($this->_interval == 0) {
                        return false;
                }
                if ($this->_cache->exists(sprintf("%s-housekeep", $table), $this->_interval)) {
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
                $this->_cache->save(sprintf("%s-housekeep", $table), (int) time(), $this->_interval);

                // 
                // This table should be housekeeped:
                // 
                return true;
        }

        /**
         * Set cache coherence options.
         * @param array $options The cache coherence options.
         */
        public function setOptions($options)
        {
                if (isset($options['resolve'])) {
                        $this->setMode($options['resolve']);
                }
                if (isset($options['housekeep'])) {
                        $this->setInterval($options['housekeep']);
                }
        }

        /**
         * Set cache conflict resolve mode (one of the ON_CONFLICT_XXX constants).
         * 
         * @param int|string $mode The conflict resolve mode.
         * @throws Exception
         */
        public function setMode($mode)
        {
                if (is_string($mode)) {
                        switch ($mode) {
                                case 'ignore':
                                        $mode = self::ON_CONFLICT_IGNORE;
                                        break;
                                case 'readd':
                                        $mode = self::ON_CONFLICT_READD;
                                        break;
                                case 'restore':
                                        $mode = self::ON_CONCLICT_RESTORE;
                                        break;
                                case 'delete':
                                        $mode = self::ON_CONFLICT_DELETE;
                                        break;
                                case 'clean':
                                        $mode = self::ON_CONFLICT_CLEAN;
                                        break;
                                case 'purge':
                                        $mode = self::ON_CONFLICT_PURGE;
                                        break;
                                default:
                                        throw new Exception("Unknown cache conflict resolve mode $mode");
                        }
                }
                if (is_int($mode)) {
                        if ($mode < self::ON_CONFLICT_IGNORE ||
                            $mode > self::ON_CONFLICT_PURGE) {
                                throw new Exception("Invalid cache conflict resolve mode $mode");
                        }
                }

                $this->_resolve = $mode;
        }

        /**
         * Set housekeeping interval.
         * Use 0 to disable housekeeping of all table indexes.
         * 
         * @param int $seconds The interval in seconds.
         */
        public function setInterval($seconds)
        {
                $this->_interval = (int) $seconds;
        }

}
