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
// File:    Handler.php
// Created: 2017-09-14 14:26:37
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\CacheBackend;
use Phalcon\Db\AdapterInterface;

/**
 * The base class for mediator handlers.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class MediatorHandler implements MediatorInterface
{

        /**
         * The database adapter.
         * @var AdapterInterface
         */
        protected $_adapter;
        /**
         * The query cache.
         * @var CacheBackend
         */
        protected $_cache;
        /**
         * The table exclude filter.
         * @var array 
         */
        protected $_exclude;

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param CacheBackend $cache The query cache backend.
         */
        protected function __construct($adapter, $cache)
        {
                $this->_cache = $cache;
                $this->_adapter = $adapter;
        }

        /**
         * This mediator can cache queries.
         * @return bool
         */
        public function canCache()
        {
                return $this->hasCache();
        }

        /**
         * Check if cache is set.
         * @return bool
         */
        public function hasCache()
        {
                return isset($this->_cache);
        }

        /**
         * Get query cache.
         * @return CacheBackend
         */
        public function getCache()
        {
                return $this->_cache;
        }

        /**
         * Set database adapter.
         * @param AdapterInterface $adapter The database adapter.
         */
        public function setAdapter($adapter)
        {
                $this->_adapter = $adapter;
        }

        /**
         * Get database adapter.
         * @return AdapterInterface
         */
        public function getAdapter()
        {
                return $this->_adapter;
        }

        /**
         * Check if adapter is set.
         * @return bool
         */
        public function hasAdapter()
        {
                return isset($this->_adapter);
        }

        /**
         * Inserts data into a table using custom RDBMS SQL syntax.
         * 
         * @param string|array $table The target table(s).
         * @param array $values The field values.
         * @param array $fields The field names (optional).
         * @param array $dataTypes The field datatype mapping (optional).
         * @return boolean
         */
        public function insert($table, array $values, $fields = null, $dataTypes = null)
        {
                return $this->_adapter->insert($table, $values, $fields, $dataTypes);
        }

        /**
         * Updates data on a table using custom RBDM SQL syntax.
         * 
         * @param string|array $table The target table(s).
         * @param array $fields The field names.
         * @param array $values The field values.
         * @param array|string $whereCondition The affected records selection (optional).
         * @param array $dataTypes The field datatype mapping (optional).
         * @return boolean
         */
        public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
        {
                return $this->_adapter->update($table, $fields, $values, $whereCondition, $dataTypes);
        }

        /**
         * Deletes data from a table using custom RBDM SQL syntax.
         * 
         * @param string|array $table The target table(s).
         * @param array|string $whereCondition The affected records selection (optional).
         * @param array $placeholders The bind parameters (optional).
         * @param array $dataTypes The field datatype mapping (optional).
         * @return boolean
         */
        public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
        {
                return $this->_adapter->delete($table, $whereCondition, $placeholders, $dataTypes);
        }

        /**
         * Check if cache key exist.
         * @param string $keyName The query cache key.
         * @return bool 
         */
        public function exist($keyName)
        {
                return $this->_cache->exists($keyName);
        }

        /**
         * Fetch cached query data.
         * @param string $keyName The query cache key.
         * @return SerializableResultSet
         */
        public function fetch($keyName)
        {
                return $this->_cache->get($keyName);
        }

        /**
         * Save query result in cache.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The content to store in cache.
         * @param array $tables The affected tables.
         */
        public function store($keyName, $content, $tables)
        {
                $this->_cache->save($keyName, $content, $tables);
        }

        /**
         * Set exclusion filter.
         * 
         * If merge is true, then tables will be replaced while result
         * settings will be merged.
         * 
         * <code>
         * // 
         * // Exclude table locks and settings (replace). All count queries
         * // are no longer cached in addition to default empty.
         * // 
         * $mediator->setFilter(array(
         *      'tables' => array('locks', 'settings'),
         *      'result' => array('count' => true)
         * );
         * </code>
         * 
         * @param array $exclude The array of tables.
         * @param boolean $merge Merge with existing filter options.
         */
        public function setFilter($exclude, $merge = true)
        {
                if ($merge === false) {
                        $this->_exclude = $exclude;
                } else {
                        if (isset($exclude['tables'])) {
                                $this->_exclude['tables'] = $exclude['tables'];
                        }
                        if (isset($exclude['result'])) {
                                $this->_exclude['result'] = array_merge($this->_exclude['result'], $exclude['result']);
                        }
                        if (isset($exclude['filter'])) {
                                $this->_exclude['filter'] = $exclude['filter'];
                        }
                }
        }

        /**
         * Get exclusion filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_exclude;
        }

}
