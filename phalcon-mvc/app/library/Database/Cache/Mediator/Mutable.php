<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Mutable.php
// Created: 2017-10-20 15:36:07
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Native as NativeCache;
use OpenExam\Library\Database\Cache\Mediator\Storage\Shared as SharedStorage;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * The mutable mediator.
 * 
 * All result sets from queries are cached for the duration of the request.
 * When terminating, all cached keys (and its data) are removed. If any 
 * table is modified, then the cache is flushed.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Mutable extends MediatorHandler implements MediatorInterface
{

        /**
         * The key store.
         * @var SharedStorage
         */
        private $_store;

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, new NativeCache($cache));
                $this->_store = new SharedStorage();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                $this->flush();
        }

        public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
        {
                $this->flush();
                return parent::delete($table, $whereCondition, $placeholders, $dataTypes);
        }

        public function insert($table, array $values, $fields = null, $dataTypes = null)
        {
                $this->flush();
                return parent::insert($table, $values, $fields, $dataTypes);
        }

        public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
        {
                $this->flush();
                return parent::update($table, $fields, $values, $whereCondition, $dataTypes);
        }

        public function setCache($cache)
        {
                $this->_cache = new NativeCache($cache);
        }

        public function store($keyName, $content, $tables)
        {
                $this->_cache->save($keyName, $content, $tables);
                $this->_store->add($keyName);
        }

        /**
         * Flush all cached keys.
         */
        private function flush()
        {
                foreach ($this->_store->keys() as $keyName) {
                        $this->_cache->delete($keyName);
                        $this->_store->remove($keyName);
                }
        }

}
