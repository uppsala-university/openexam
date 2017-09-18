<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    MediatorInteface.php
// Created: 2017-09-14 12:46:04
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\CacheBackend;
use OpenExam\Library\Database\Cache\Result\Serializable as SerializableResultSet;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * Interface for cached database adapters.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface MediatorInterface
{

        /**
         * Inserts data into a table using custom RDBMS SQL syntax.
         * 
         * @param string|array $table The target table(s).
         * @param array $values The field values.
         * @param array $fields The field names (optional).
         * @param array $dataTypes The field datatype mapping (optional).
         * @return boolean
         */
        public function insert($table, array $values, $fields = null, $dataTypes = null);

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
        public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null);

        /**
         * Deletes data from a table using custom RBDM SQL syntax.
         * 
         * @param string|array $table The target table(s).
         * @param array|string $whereCondition The affected records selection (optional).
         * @param array $placeholders The bind parameters (optional).
         * @param array $dataTypes The field datatype mapping (optional).
         * @return boolean
         */
        public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null);

        /**
         * Check if cache key exist.
         * @param string $keyName The query cache key.
         * @return bool 
         */
        public function exist($keyName);

        /**
         * Fetch cached query data.
         * @param string $keyName The query cache key.
         * @return SerializableResultSet
         */
        public function fetch($keyName);

        /**
         * Save query result in cache.
         * 
         * @param string $keyName The cache key.
         * @param mixed $content The content to store in cache.
         * @param array $tables The affected tables.
         */
        public function store($keyName, $content, $tables);

        /**
         * This mediator can cache queries.
         * @return bool
         */
        public function canCache();

        /**
         * Set query cache.
         * @param BackendInterface $cache The query cache.
         */
        public function setCache($cache);

        /**
         * Check if cache is set.
         * @return bool
         */
        public function hasCache();

        /**
         * Get query cache.
         * @return CacheBackend
         */
        public function getCache();

        /**
         * Set database adapter.
         * @param AdapterInterface $adapter The database adapter.
         */
        public function setAdapter($adapter);

        /**
         * Get database adapter.
         * @return AdapterInterface
         */
        public function getAdapter();

        /**
         * Check if adapter is set.
         * @return bool
         */
        public function hasAdapter();
}
