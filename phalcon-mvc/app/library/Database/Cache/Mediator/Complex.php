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
// File:    Complex.php
// Created: 2017-09-14 14:06:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Indexed as IndexedCache;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

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
