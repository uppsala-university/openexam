<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Mutex.php
// Created: 2017-04-25 12:18:45
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Synchronize;

use Phalcon\Cache\BackendInterface;

/**
 * Single mutex lock.
 * 
 * Provides a mutex implemented using a cache backend to control access to a
 * critical section. Becomes a distributed mutex if the cache backend is a
 * network enabled (i.e. memcached or redis).
 * 
 * <code>
 * $mutex = new Mutex($this->cache);
 * try {
 *      if ($mutex->acquire('resource')) {
 *              // Critical section code here...
 *      }
 * } finally {
 *      $mutex->release();
 * }
 * </code>
 * 
 * The factory method create() can be used to create a mutex object using
 * default delay and retry:
 * 
 * <code>
 * if (($mutex = Mutex::create($this->cache, 'resource'))) {
 *      // Critical section code here...
 *      $mutex->release();        
 * }
 * </code>
 * 
 * The critical section can be accessed non-blocking using a callback 
 * function:
 * 
 * <code>
 * $result = $mutex->open('resource', function($mutex) use($data) {
 *      // Use data inside critical section...
 * });
 * </code>
 * 
 * Disclaimer:
 * -------------------
 * A standard mutex has kernel support for efficient block of the calling 
 * thread. As this is a distributed mutex, we have to resort to polling at
 * configured interval.
 * 
 * @property-read int $delay The retry delay in milliseconds.
 * @property-read int $retry The number of retries.
 * @property-read boolean $locked This mutex is locked.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Mutex
{

        /**
         * Default retry delay.
         */
        const LOCK_DELAY = 100;
        /**
         * Default retry times.
         */
        const LOCK_RETRY = 2;
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
         * The acquired lock.
         * @var string 
         */
        private $_lock;
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
        public function __construct($cache, $delay = 100, $retry = 2)
        {
                $this->_cache = $cache;

                $this->_delay = $delay * 1000;
                $this->_retry = $retry;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                if (isset($this->_lock)) {
                        $this->_cache->delete($this->_lock);
                }
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'delay':
                                return $this->_delay;
                        case 'retry':
                                return $this->_retry;
                        case 'locked':
                                return isset($this->_lock);
                }
        }

        /**
         * Acquire resource lock.
         * 
         * @param string $resource The resource name.
         * @param int $ttl TTL for lock.
         * @return boolean
         */
        public function acquire($resource, $ttl = 5)
        {
                if (isset($this->_lock)) {
                        return false;
                } else {
                        return $this->trylock($resource, $ttl);
                }
        }

        /**
         * Release resource lock.
         * @return boolean
         */
        public function release()
        {
                if (!isset($this->_lock)) {
                        return false;
                } else {
                        return $this->unlock($this->_lock);
                }
        }

        /**
         * Try create mutex lock.
         * 
         * @param string $resource The resource name.
         * @param int $ttl The TTL for cache key.
         * @return boolean
         */
        private function trylock($resource, $ttl)
        {
                $lock = sprintf("%s-lock", $resource);

                for ($i = 0; $i <= $this->_retry; ++$i) {
                        if ($this->lock($lock, $ttl)) {
                                return true;
                        } elseif ($i != $this->_retry) {
                                usleep($this->_delay);
                        }
                }

                return false;
        }

        /**
         * Create mutex lock.
         * 
         * @param string $lock The lock key.
         * @param int $ttl The TTL for cache key.
         * @return boolean
         */
        private function lock($lock, $ttl)
        {
                if ($this->_cache->exists($lock)) {
                        return false;
                }

                $this->_cache->save($lock, null, $ttl);
                $this->_lock = $lock;

                return true;
        }

        /**
         * Delete mutex lock.
         * 
         * @param int $ttl The TTL for cache key.
         * @param string $lock The lock key.
         * @return boolean
         */
        private function unlock($lock)
        {
                if (!$this->_cache->exists($lock)) {
                        return false;
                }

                $this->_cache->delete($lock);
                $this->_lock = null;

                return true;
        }

        /**
         * Create mutex and acquire lock.
         * 
         * Return a new mutex if lock was successful acquired, otherwise this
         * function returns false.
         * 
         * @param BackendInterface $cache The cache backend.
         * @param string $resource The resource name.
         * @param int $ttl TTL for lock.
         * @return Mutex
         */
        public static function create($cache, $resource, $ttl)
        {
                $mutex = new Mutex($cache);

                if ($mutex->acquire($resource, $ttl)) {
                        return $mutex;
                } else {
                        return false;
                }
        }

        /**
         * Open critical section if available.
         * 
         * This method can be used for non-blocking access to a critical
         * section controlled by $resource. If lock was successful acquired, 
         * then the callback gets called.
         * 
         * The return value is either from callback (if lock was acquired) 
         * or false if critical section was already locked. The mutex is
         * automatic released when function call returns.
         * 
         * @param string $resource The resource name.
         * @param callable $callback The callback function.
         * @param int $ttl TTL for lock.
         * @return mixed
         */
        public function open($resource, $callback, $ttl = 5)
        {
                if (isset($this->_lock)) {
                        return false;
                }

                try {
                        $lock = sprintf("%s-lock", $resource);

                        if ($this->lock($lock, $ttl)) {
                                return call_user_func($callback, $this);
                        } else {
                                return false;
                        }
                } finally {
                        $this->release();
                }
        }

}
