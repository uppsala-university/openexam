<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Request.php
// Created: 2017-09-14 14:09:00
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Native as NativeCache;

/**
 * The request mediator.
 * 
 * All result sets from queries are cached for the duration of the request.
 * When terminating, all cached keys (and its data) are removed.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Request extends MediatorHandler implements MediatorInterface
{

        /**
         * The cached keys.
         * @var array 
         */
        private $_cached = array();

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, new NativeCache($cache));
        }

        public function __destruct()
        {
                foreach (array_keys($this->_cached) as $keyName) {
                        $this->_cache->delete($keyName);
                }
        }

        public function setCache($cache)
        {
                $this->_cache = new NativeCache($cache);
        }

        public function store($keyName, $content, $tables)
        {
                $this->_cache->save($keyName, $content, $tables);
                $this->_cached[$keyName] = true;
        }

}
