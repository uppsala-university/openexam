<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Incremental.php
// Created: 2017-04-25 13:38:33
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Synchronize;

use Phalcon\Cache\BackendInterface;

/**
 * Multiple mutex lock.
 * 
 * Provides a mutex implemented using a cache backend to control access to a
 * critical section. This class will internal keep track of acquired resource
 * and is meant for incremental/nested access protection. 
 * 
 * Becomes a distributed mutex if the cache backend is a network enabled 
 * (i.e. memcached or redis).
 * 
 * <code>
 * $mutex = new Mutex($this->cache);
 * try {
 *      if ($mutex->acquire('resource')) {
 *              // Critical section code here...
 *      }
 * } finally {
 *      $mutex->release('resource');
 * }
 * </code>
 * 
 * All acquired locks are released automatic when object is destroyed. It
 * can prematured be done by calling $mutex->cleanup(). The object should 
 * no longer be used after this call.
 * 
 * @property-read int $delay The retry delay in milliseconds.
 * @property-read int $retry The number of retries.
 * @property-read array $locks All acquired resource locks.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Incremental
{

        /**
         * Default retry delay.
         */
        const LOCK_DELAY = 200;
        /**
         * Default retry times.
         */
        const LOCK_RETRY = 3;
        /**
         * Default TTL for lock.
         */
        const LOCK_TTL = 5;

        /**
         * The cache backend.
         * @var BackendInterface 
         */
        private $_cache;
        /**
         * Acquired resource locks.
         * @var array 
         */
        private $_locks;
        /**
         * The retry delay in milliseconds.
         * @var int 
         */
        private $_delay;
        /**
         * The number of retries.
         * @var int 
         */
        private $_retry;

        /**
         * Constructor.
         * @param BackendInterface $cache The cache backend.
         */
        public function __construct($cache, $delay = 200, $retry = 3)
        {
                $this->_cache = $cache;

                $this->_delay = $delay;
                $this->_retry = $retry;

                $this->_locks = array();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                $this->cleanup();
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'delay':
                                return $this->_delay;
                        case 'retry':
                                return $this->_retry;
                        case 'locks':
                                return $this->_locks;
                }
        }

        /**
         * Clean up all acquired locks.
         */
        public function cleanup()
        {
                foreach ($this->_locks as $lock) {
                        $this->_cache->delete($lock);
                }
        }

        /**
         * Acquire resource lock.
         * 
         * @param string $resource The resource name.
         * @return boolean
         */
        public function acquire($resource, $ttl = 5)
        {
                $lock = sprintf("%s-lock", $resource);

                for ($i = 0; $i < $this->_retry; ++$i) {
                        if ($this->lock($resource, $ttl, $lock)) {
                                return true;
                        } else {
                                usleep($this->_delay);
                        }
                }

                return false;
        }

        /**
         * Release resource lock.
         * 
         * @param string $resource The resource name.
         * @return boolean
         */
        public function release($resource)
        {
                $lock = sprintf("%s-lock", $resource);

                if (!array_key_exists($resource, $this->_locks)) {
                        return false;
                }
                if (!$this->_cache->exists($lock)) {
                        return false;
                }

                $this->_cache->delete($lock);
                unset($this->_locks[$resource]);

                return true;
        }

        /**
         * Set mutex lock.
         * 
         * @param string $resource The resource name.
         * @param int $ttl The TTL for cache key.
         * @param string $lock The lock key.
         * @return boolean
         */
        private function lock($resource, $ttl, $lock)
        {
                if (array_key_exists($resource, $this->_locks)) {
                        return false;
                }
                if ($this->_cache->exists($lock)) {
                        return false;
                }

                $this->_cache->save($lock, null, $ttl);
                $this->_locks[$resource] = $lock;

                return true;
        }

}
