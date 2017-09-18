<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Complex.php
// Created: 2017-09-14 14:06:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Indexed as IndexedCache;
use Phalcon\Cache\BackendInterface;

/**
 * The complex mediator.
 * 
 * Mediator handler that keeps per table indexes of cache keys and upon
 * modify of a table invalidates all related cache keys and purges the
 * key index.
 * 
 * Relies on the indexed cache backend for maintaining the cache key
 * indexes and cleanup (locked by mutex during modify).
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Complex extends MediatorHandler implements MediatorInterface
{

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, new IndexedCache($cache));
        }

        public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
        {
                $this->onChanged($table);
                return parent::delete($table, $whereCondition, $placeholders, $dataTypes);
        }

        public function insert($table, array $values, $fields = null, $dataTypes = null)
        {
                $this->onChanged($table);
                return parent::insert($table, $values, $fields, $dataTypes);
        }

        public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
        {
                $this->onChanged($table);
                return parent::update($table, $fields, $values, $whereCondition, $dataTypes);
        }

        /**
         * Handle on table changed call.
         * @param string|array $table The table name(s).
         */
        private function onChanged($table)
        {
                if (is_string($table)) {
                        if (!in_array($table, $this->_exclude['tables'])) {
                                $this->_cache->delete($table);
                        }
                }
                if (is_array($table)) {
                        foreach ($table as $t) {
                                if (!in_array($t, $this->_exclude['tables'])) {
                                        $this->_cache->delete($t);
                                }
                        }
                }
        }

        /**
         * Set query cache.
         * @param BackendInterface $cache The query cache.
         */
        public function setCache($cache)
        {
                $this->_cache = new IndexedCache($cache);
        }

}
