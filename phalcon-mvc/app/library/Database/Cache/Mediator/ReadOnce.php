<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ReadOnce.php
// Created: 2017-09-14 14:07:24
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Native as NativeCache;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * The read-once mediator.
 * 
 * Each query is cached. Upon read next time, the cached data is fetched 
 * and the cache key is removed.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ReadOnce extends MediatorHandler implements MediatorInterface
{

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, new NativeCache($cache));
        }

        public function fetch($keyName)
        {
                $data = $this->_cache->get($key);
                $this->_cache->delete($key);
                return $data;
        }

        public function setCache($cache)
        {
                $this->_cache = new NativeCache($cache);
        }

}
