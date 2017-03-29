<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Mediator.php
// Created: 2017-01-10 01:38:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache;

use OpenExam\Library\Database\Cache\Result\Coherence;
use OpenExam\Library\Database\Cache\Result\Serializable as SerializableResultSet;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db as PhalconDb;
use Phalcon\Db\AdapterInterface;
use Phalcon\Kernel as PhalconKernel;

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
         * The query cache.
         * @var Backend
         */
        private $_cache;
        /**
         * Tables to exclude.
         * @var array 
         */
        private $_exclude;
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
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter = null, $cache = null)
        {
                if (isset($cache)) {
                        $this->_cache = new Backend($cache);
                }
                if (isset($adapter)) {
                        $this->_adapter = $adapter;
                }

                $this->_exclude = array(
                        'tables' => array('answers'),
                        'filter' => function($table, $data) {
                                if ($table == 'locks' && $data->numRows() == 0) {
                                        return true;
                                } else {
                                        return false;
                                }
                        }
                );
        }

        /**
         * Set query cache.
         * @param BackendInterface $cache The query cache.
         */
        public function setCache($cache)
        {
                if (isset($cache)) {
                        $this->_cache = new Backend($cache);
                }
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
         * @return Backend
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
                if (isset($adapter)) {
                        $this->_adapter = $adapter;
                }
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
         * Set tables to exclude.
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
                if ($merge) {
                        if (isset($exclude['tables'])) {
                                $this->_exclude['tables'] = $exclude['tables'];
                        }
                        if (isset($exclude['result'])) {
                                $this->_exclude['result'] = array_merge($this->_exclude['result'], $exclude['result']);
                        }
                        if (isset($exclude['filter'])) {
                                $this->_exclude['filter'] = $exclude['filter'];
                        }
                } else {
                        $this->_exclude = $exclude;
                }
        }

        /**
         * Set cache coherence options.
         * @param array $options The cache coherence options.
         */
        public function setCoherence($options)
        {
                $this->_cache->setCoherence($options);
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
                $this->_cache->delete($table);
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
                $this->_cache->delete($table);
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
                $this->_cache->delete($table);
                return $this->_adapter->delete($table, $whereCondition, $placeholders, $dataTypes);
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
                // Can't cache queries not using SQL string:
                // 
                if (!is_string($sqlStatement)) {
                        return $this->_adapter->query($sqlStatement, $bindParams, $bindTypes);
                }

                // 
                // Compute cache key:
                // 
                if (is_array($bindParams)) {
                        $keyName = PhalconKernel::preComputeHashKey($sqlStatement . '//' . join('|', $bindParams));
                } else {
                        $keyName = PhalconKernel::preComputeHashKey($sqlStatement);
                }

                // 
                // Get result set from cache if existing:
                // 
                if ($this->_cache->exists($keyName)) {
                        if (($data = $this->_cache->get($keyName)) !== null) {
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
                // Collect jointed tables in array:
                // 
                $tables = array('access', 'admins', 'audit', 'computers', 'contributors', 'correctors', 'decoders', 'exams', 'files', 'invigilators', 'locks', 'notify', 'performance', 'profile', 'questions', 'resources', 'results', 'rooms', 'sessions', 'settings', 'students', 'teachers', 'topics', 'users', 'answers');
                $cached = array();

                foreach ($tables as $table) {
                        if (strpos($sqlStatement, $table) > 0) {
                                $cached[] = $table;
                        }
                }

                // 
                // Check table exclude filter.
                // 
                if (in_array($cached[0], $this->_exclude['tables'])) {
                        return $data;
                }

                // 
                // Check result exclude filter.
                // 
                if (isset($this->_exclude['result'])) {
                        if ($this->_exclude['result']['count']) {
                                if (strncmp($sqlStatement, 'SELECT COUNT', 12) == 0) {
                                        return $data;
                                }
                        }
                        if ($this->_exclude['result']['null']) {
                                if (is_null($data)) {
                                        return $data;
                                }
                        }
                        if ($this->_exclude['result']['false']) {
                                if (is_bool($data) && $data === false) {
                                        return $data;
                                }
                        }
                        if ($this->_exclude['result']['empty']) {
                                if ($data->numRows() == 0) {
                                        return $data;
                                }
                        }
                }

                // 
                // Check primary table filter.
                // 
                if (isset($this->_exclude['filter'])) {
                        if (is_callable($this->_exclude['filter'])) {
                                if (call_user_func($this->_exclude['filter'], $cached[0], $data)) {
                                        return $data;
                                }
                        }
                        if (is_array($this->_exclude['filter']) && isset($this->_exclude['filter'][$cached[0]])) {
                                if ($this->filter($sqlStatement, $data, $this->_exclude['filter'][$cached[0]])) {
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
                        $this->_cache->save($keyName, $result, $cached);

                        return $result;
                } else {
                        $this->_cache->save($keyName, $data, $cached);
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

}
