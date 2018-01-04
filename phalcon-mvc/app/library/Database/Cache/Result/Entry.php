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
// File:    Entry.php
// Created: 2017-03-24 10:59:05
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Result;

use OpenExam\Library\Database\Exception;
use Phalcon\Cache\BackendInterface;
use Serializable;

/**
 * Result set entry.
 * 
 * This class represents an result set cache entry and provides methods for
 * checking cache coherence. The cache format is:
 * 
 * <code>
 * $entry = array(
 *      'content' => mixed,     // Might be a serialized result set.
 *      'tables'  => array()    // The related tables.
 * )
 * </code>
 * 
 * The cache might become invalid if the table index (a special cache entry
 * for a single database table containing all cached related result sets)
 * is deleted. In that case we might face a situation where result sets are
 * cached, but its impossible to invalidate them.
 * 
 * This class tries to solve the cache coherence problem by providing a
 * standard format and methods for checking cache entry validity, and if
 * needed, rebuild the table index.
 * 
 * The cache conflict resolve mode can be set by calling setResolveMode() and
 * default to delete. The resolve mode affects how refresh() handles an 
 * condition where i.e. the result set exist in cache, but is missing in the
 * table index. 
 * 
 * The default behavior is to deleted invalid cache entries, but leaving table 
 * indexes intact. A stronger variant of destructive conflict resolution is to
 * use the purge mode.
 * 
 * @property-read string $ckey The cache key.
 * @property-read mixed $content The cached content.
 * @property-read array $tables The related database tables.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Entry implements Serializable
{

        /**
         * The content section in entry.
         */
        const SECT_CONTENT = 'content';
        /**
         * The related database tables section in entry.
         */
        const SECT_TABLES = 'tables';

        /**
         * The cache key.
         * @var string 
         */
        private $_ckey;
        /**
         * The cached content.
         * @var mixed 
         */
        private $_content;
        /**
         * The related database tables.
         * @var array 
         */
        private $_tables;
        /**
         * This entry is invalid.
         * @var boolean 
         */
        private $_invalid = false;
        /**
         * This entry is missing.
         * @var boolean 
         */
        private $_missing = false;
        /**
         * This entry has been checked.
         * @var boolean 
         */
        private $_checked = false;

        /**
         * Constructor.
         * 
         * @param string $ckey The cache key.
         * @param array $data The cache data.
         * @throws Exception
         */
        public function __construct($ckey, $data = null)
        {
                $this->_ckey = $ckey;

                if (isset($data)) {
                        if (!isset($data['content'])) {
                                throw new Exception("Invalid cache entry format (missing content section)");
                        } else {
                                $this->_content = $data['content'];
                        }
                        if (!isset($data['tables'])) {
                                throw new Exception("Invalid cache entry format (missing tables section)");
                        } else {
                                $this->_tables = $data['tables'];
                        }
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_ckey);
                unset($this->_content);
                unset($this->_tables);
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'content':
                                return $this->_content;
                        case 'tables':
                                return $this->_tables;
                        case 'ckey':
                                return $this->_ckey;
                }
        }

        /**
         * Set entry content.
         * @param mixed $content The entry content.
         */
        public function setContent($content)
        {
                $this->_content = $content;
        }

        /**
         * Set related tables.
         * @param array $tables The related tables.
         */
        public function setTables(array $tables)
        {
                $this->_tables = $tables;
        }

        /**
         * Get entry content.
         * @return mixed
         */
        public function getContent()
        {
                return $this->_content;
        }

        /**
         * Get related tables.
         * @return array
         */
        public function getTables()
        {
                return $this->_tables;
        }

        public function serialize()
        {
                return serialize(array(
                        $this->_ckey, $this->_content, $this->_tables
                ));
        }

        public function unserialize($serialized)
        {
                list($this->_ckey, $this->_content, $this->_tables) = unserialize($serialized);
        }

        /**
         * Read entry from cache backend.
         * 
         * @param BackendInterface $cache The cache backend.
         * @param int $ttlres The TTL for result set cache.
         * @return boolean True if successful found.
         */
        public function fetch($cache, $ttlres)
        {
                if (!($data = $cache->get($this->_ckey, $ttlres))) {
                        $this->_invalid = true;
                        return false;
                } else {
                        $this->_content = $data['content'];
                        $this->_tables = $data['tables'];
                        $this->_invalid = false;
                }
        }

        /**
         * Find entry in cache backend.
         * 
         * @param string $ckey The cache key.
         * @param BackendInterface $cache The cache backend.
         * @param int $ttlres The TTL for result set cache.
         * @return Entry The cache entry.
         */
        public static function find($ckey, $cache, $ttlres)
        {
                if (!($data = $cache->get($ckey, $ttlres))) {
                        return false;
                } else {
                        return new Entry($ckey, $data);
                }
        }

        /**
         * Delete this entry in cache.
         * @param BackendInterface $cache The cache backend.
         */
        public function delete($cache)
        {
                $cache->delete($this->_ckey);

                $this->_content = null;
                $this->_tables = array();

                $this->_invalid = true;
                $this->_missing = true;
        }

        /**
         * Save entry to cache.
         * 
         * @param BackendInterface $cache The cache backend.
         * @param int $ttlres The TTL for result set.
         */
        public function save($cache, $ttlres)
        {
                $cache->save($this->_ckey, $this, $ttlres);
                $this->_missing = false;
        }

        /**
         * Check cache entry coherence.
         * 
         * The entry is coherent if cached entry exist and is present in all 
         * related database table indexes.
         * 
         * @param BackendInterface $cache The cache backend.
         * @param int $ttlres The TTL for result set cache.
         * @param int $ttlidx The TTL for table index.
         * @return boolean
         */
        public function validate($cache, $ttlres, $ttlidx)
        {
                // 
                // Check this cache entry first:
                // 
                if (!$cache->exists($this->_ckey, $ttlres)) {
                        $this->_missing = true;
                        $this->_invalid = true;
                        $this->_checked = true;
                        return false;
                }

                // 
                // Requires table indexes:
                // 
                if (!isset($this->_tables)) {
                        $this->_invalid = true;
                        $this->_checked = true;
                        return false;
                }

                // 
                // Check all related table indexes:
                // 
                foreach ($this->_tables as $table) {
                        if (!$cache->exists($table, $ttlidx)) {
                                $this->_invalid = true;
                                $this->_checked = true;
                                return false;
                        }
                        if (!($data = $cache->get($table, $ttlidx))) {
                                throw new Exception("Missing $table table index in cache");
                        } else {
                                if (!in_array($this->_ckey, $data)) {
                                        $this->_invalid = true;
                                        $this->_checked = true;
                                        return false;
                                }
                        }
                }

                // 
                // This cache entry is valid:
                // 
                $this->_invalid = false;
                $this->_checked = true;

                return true;
        }

        /**
         * Set conflict resolved.
         */
        public function setResolved()
        {
                $this->_invalid = false;
                $this->_missing = false;
        }

        /**
         * Check if entry is invalid.
         * @return boolean
         */
        public function isInvalid()
        {
                return $this->_invalid;
        }

        /**
         * Check if entry is missing.
         * @return boolean
         */
        public function isMissing()
        {
                return $this->_missing;
        }

        /**
         * This entry has been checked.
         * @return boolean
         */
        public function isChecked()
        {
                return $this->_checked;
        }

}
