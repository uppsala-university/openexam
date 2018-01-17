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
// File:    Mediator.php
// Created: 2017-01-10 01:38:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache;

use OpenExam\Library\Database\Cache\Backend\Indexed as IndexedBackend;
use OpenExam\Library\Database\Cache\Mediator\Complex as ComplexMediator;
use OpenExam\Library\Database\Cache\Mediator\Direct as DirectMediator;
use OpenExam\Library\Database\Cache\Mediator\MediatorHandler;
use OpenExam\Library\Database\Cache\Mediator\MediatorInterface;
use OpenExam\Library\Database\Cache\Mediator\Mutable as MutableMediator;
use OpenExam\Library\Database\Cache\Mediator\ReadOnce as ReadOnceMediator;
use OpenExam\Library\Database\Cache\Mediator\Request as RequestMediator;
use OpenExam\Library\Database\Cache\Mediator\Simple as SimpleMediator;
use OpenExam\Library\Database\Cache\Result\Serializable as SerializableResultSet;
use OpenExam\Library\Database\Exception as DatabaseException;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db as PhalconDb;
use Phalcon\Db\ResultInterface;

/**
 * Mediator for cached database adapters.
 * 
 * This class masquerades as an database adapter for the application and acts 
 * as a bridge between the real adapter and the cache. It takes care of cache 
 * invalidation and fetches query result sets from cache whenever possible.
 * 
 * For greatest value, this class should be used with an deferred database 
 * adapter. In that case the number of database connections are keept to a
 * minimum.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Mediator extends Proxy
{

        /**
         * The mediator handler.
         * @var MediatorHandler 
         */
        private $_handler;
        /**
         * The minimum number of records.
         * @var int 
         */
        private $_min = 0;
        /**
         * The maximum number of records.
         * @var int 
         */
        private $_max = 0;

        /**
         * Constructor.
         * @param MediatorHandler $handler The mediator handler.
         */
        public function __construct($handler)
        {
                $exclude = array(
                        'tables' => array('answers', 'audit', 'profile'),
                        'filter' => function($table, $data) {
                                if ($table == 'locks' && $data->numRows() == 0) {
                                        return true;
                                } else {
                                        return false;
                                }
                        }
                );

                $this->_adapter = $handler->getAdapter();
                $this->_handler = $handler;
                $this->_handler->setFilter($exclude);
        }

        /**
         * Check if mediator can invalidate cache.
         * @return boolean
         */
        public function canInvalidate()
        {
                if ($this->_handler->canCache() &&
                    $this->_handler->hasCache()) {
                        if ($this->_handler->getCache() instanceof IndexedBackend) {
                                return true;
                        } else {
                                return false;
                        }
                }
        }

        /**
         * Set mediator handler.
         * @param MediatorInterface $handler
         */
        public function setHandler($handler)
        {
                $this->_handler = $handler;
        }

        /**
         * Get mediator handler.
         * @return MediatorInterface
         */
        public function getHandler()
        {
                return $this->_handler;
        }

        /**
         * Set result set row limits.
         * 
         * Don't cache result sets having less than min rows or more than
         * max rows. The defaults are unlimited.
         * 
         * @param int $min The minimum number of records.
         * @param int $max The maximum number of records.
         */
        public function setRange($min = 0, $max = 0)
        {
                $this->_min = $min;
                $this->_max = $max;
        }

        /**
         * Set minimum cache size.
         * @param int $min The minimum record count.
         */
        public function setMinimun($min = 0)
        {
                $this->_min = $min;
        }

        /**
         * Set maximum cache size.
         * @param int $max The maximum record count.
         */
        public function setMaximun($max = 0)
        {
                $this->_max = $max;
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
                return $this->_handler->insert($table, $values, $fields, $dataTypes);
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
                return $this->_handler->update($table, $fields, $values, $whereCondition, $dataTypes);
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
                return $this->_handler->delete($table, $whereCondition, $placeholders, $dataTypes);
        }

        /**
         * Query data from database.
         * 
         * Sends SQL statements to the database server returning the success 
         * state. Use this method only when the SQL statement sent to the 
         * server is returning rows.
         * 
         * @param mixed $sqlStatement The SQL query.
         * @param mixed $bindParams The bind parameters (optional).
         * @param mixed $bindTypes The parameter type mapping (optional).
         * @return boolean|SerializableResultSet
         */
        public function query($sqlStatement, $bindParams = null, $bindTypes = null)
        {
                // 
                // Bypass if query is not string or handler is not caching:
                // 
                if (!is_string($sqlStatement) || !$this->_handler->canCache()) {
                        return $this->_adapter->query($sqlStatement, $bindParams, $bindTypes);
                }

                // 
                // Compute cache key:
                // 
                if (is_array($bindParams)) {
                        $keyName = md5($sqlStatement . '//' . join('|', $bindParams));
                } else {
                        $keyName = md5($sqlStatement);
                }

                // 
                // Check that cache key is defined:
                // 
                if (!isset($keyName)) {
                        throw new DatabaseException("Failed compute cache key");
                }

                // 
                // Get result set from cache if existing:
                // 
                if ($this->_handler->exist($keyName)) {
                        if (($data = $this->_handler->fetch($keyName)) !== null) {
                                return $data;
                        }
                }

                // 
                // Run query on database:
                // 
                if (!($data = $this->_adapter->query($sqlStatement, $bindParams, $bindTypes))) {
                        return false;
                }

                // 
                // Check record number limit.
                // 
                if (($this->_min != 0) && ($data->numRows() < $this->_min)) {
                        return $data;
                }
                if (($this->_max != 0) && ($data->numRows() > $this->_max)) {
                        return $data;
                }

                // 
                // Collect joined tables in array:
                // 
                $tables = array('answers', 'access', 'admins', 'audit', 'computers', 'contributors', 'correctors', 'decoders', 'exams', 'files', 'invigilators', 'locks', 'notify', 'performance', 'profile', 'questions', 'render', 'resources', 'results', 'rooms', 'sessions', 'settings', 'students', 'teachers', 'topics', 'users');
                $cached = array();

                foreach ($tables as $table) {
                        if (strpos($sqlStatement, $table) > 0) {
                                $cached[] = $table;
                        }
                }

                // 
                // Get table filter:
                // 
                $exclude = $this->_handler->getFilter();

                // 
                // Check table exclude filter.
                // 
                if (in_array($cached[0], $exclude['tables'])) {
                        return $data;
                }

                // 
                // Check result exclude filter.
                // 
                if (isset($exclude['result'])) {
                        if ($exclude['result']['count']) {
                                if (strncmp($sqlStatement, 'SELECT COUNT', 12) == 0) {
                                        return $data;
                                }
                        }
                        if ($exclude['result']['null']) {
                                if (is_null($data)) {
                                        return $data;
                                }
                        }
                        if ($exclude['result']['false']) {
                                if (is_bool($data) && $data === false) {
                                        return $data;
                                }
                        }
                        if ($exclude['result']['empty']) {
                                if ($data->numRows() == 0) {
                                        return $data;
                                }
                        }
                }

                // 
                // Check primary table filter.
                // 
                if (isset($exclude['filter'])) {
                        if (is_callable($exclude['filter'])) {
                                if (call_user_func($exclude['filter'], $cached[0], $data)) {
                                        return $data;
                                }
                        }
                        if (is_array($exclude['filter']) && isset($exclude['filter'][$cached[0]])) {
                                if ($this->filter($sqlStatement, $data, $exclude['filter'][$cached[0]])) {
                                        return $data;
                                }
                        }
                }

                // 
                // Save query result in cache. Result set used in model instantiation 
                // must have keys matching the column mapping, thus all result sets
                // except simple counters are fetched associative.
                // 
                if (is_object($data)) {

                        if (strncmp($sqlStatement, 'SELECT COUNT', 12) == 0) {
                                $data->setFetchMode(PhalconDb::FETCH_BOTH);
                        } else {
                                $data->setFetchMode(PhalconDb::FETCH_ASSOC);
                        }

                        $result = new SerializableResultSet($data);
                        $this->_handler->store($keyName, $result, $cached);

                        return $result;
                } else {
                        $this->_handler->store($keyName, $data, $cached);
                        return false;
                }
        }

        /**
         * Result set filter.
         * 
         * Check whether the result set (data) matches any of the filter 
         * preferences and should be excluded from caching. Returns false
         * if no filter matched.
         * 
         * @param string $sql The SQL query.
         * @param boolean|ResultInterface $data The result set.
         * @param array $match The filter preferences.
         * @return boolean
         */
        private function filter($sql, $data, $match)
        {
                if (in_array('count', $match)) {
                        if (strncmp($sql, 'SELECT COUNT', 12) == 0) {
                                return true;
                        }
                }
                if (in_array('null', $match)) {
                        if (is_null($data)) {
                                return true;
                        }
                }
                if (in_array('false', $match)) {
                        if (is_bool($data) && $data === false) {
                                return true;
                        }
                }
                if (in_array('empty', $match)) {
                        if ($data->numRows() == 0) {
                                return true;
                        }
                }
        }

        /**
         * Create mediator handler.
         * @param string $type The mediator name.
         * @param PhalconDb\AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The cache object.
         * @return MediatorHandler
         * @throws DatabaseException
         */
        public static function create($type, $adapter, $cache)
        {
                switch ($type) {
                        case 'complex':
                                return new ComplexMediator($adapter, $cache);
                        case 'direct':
                                return new DirectMediator($adapter, $cache);
                        case 'mutable':
                                return new MutableMediator($adapter, $cache);
                        case 'readonce':
                                return new ReadOnceMediator($adapter, $cache);
                        case 'request':
                                return new RequestMediator($adapter, $cache);
                        case 'simple':
                                return new SimpleMediator($adapter, $cache);
                        default:
                                throw new DatabaseException(sprintf("Unknown database mediator %s", $type));
                }
        }

}
