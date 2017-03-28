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
         * Set cache conflict resolve mode (one of the ON_CONFLICT_XXX constants).
         * 
         * @param int $mode The conflict resolve mode.
         * @throws Exception
         */
        public function setResolveMode($mode)
        {
                if ($mode < self::ON_CONFLICT_IGNORE ||
                    $mode > self::ON_CONFLICT_PURGE) {
                        throw new Exception("Invalid cache conflict resolve mode $mode");
                } else {
                        $this->_resolve = $mode;
                }
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
                                return $this->readd($entry);
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
        private function readd($entry)
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

}
