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

use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\Result\Serializable as SerializableResultSet;
use Phalcon\Kernel as PhalconKernel;
use Phalcon\Db as PhalconDb;

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
        private $_exclude = array('answers');
        /**
         * The minimum number of records.
         * @var int 
         */
        private $_min = 0;
        /**
         * The minimum number of records.
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
        }

        /**
         * Set query cache.
         * @param BackendInterface $cache The query cache.
         */
        public function setCache($cache)
        {
                $this->_cache = new Backend($cache);
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
         * Set tables to exclude.
         * @param array $exclude The array of tables.
         */
        public function setFilter($exclude)
        {
                $this->_exclude = $exclude;
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

        public function insert($table, array $values, $fields = null, $dataTypes = null)
        {
                $this->_cache->delete($table);
                return $this->_adapter->insert($table, $values, $fields, $dataTypes);
        }

        public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
        {
                $this->_cache->delete($table);
                return $this->_adapter->update($table, $fields, $values, $whereCondition, $dataTypes);
        }

        public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
        {
                $this->_cache->delete($table);
                return $this->_adapter->delete($table, $whereCondition, $placeholders, $dataTypes);
        }

        public function query($sqlStatement, $bindParams = null, $bindTypes = null)
        {
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
                if (in_array($cached[0], $this->_exclude)) {
                        return $data;
                }

                // 
                // Check record number limit.
                // 
                if (($this->_min != 0) && (count($data) < $this->_min)) {
                        return $data;
                }
                if (($this->_max != 0) && (count($data) > $this->_max)) {
                        return $data;
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

}
