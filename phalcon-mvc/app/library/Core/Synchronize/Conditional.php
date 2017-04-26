<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Conditional.php
// Created: 2017-04-25 15:47:21
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Synchronize;

/**
 * Conditional access to critical section. 
 * 
 * Block calling client until the resource to wait on becomes available. 
 * Becomes a distributed conditional block if the cache backend is a network 
 * enabled (i.e. memcached or redis).
 * 
 * <code>
 * $mutex = new Mutex($this->cache);
 * 
 * $conditional = new Conditional($mutex, 400);
 * $conditional->wait('resource', function() use($data) {
 *      // Use data in conditional blocked callback
 * });
 * </code>
 * 
 * Set TTL to expected time for callback to complete. The lock is automatic
 * removed when callback finish.
 * 
 * The wait() call is not trapping exception. If an exception is thrown from
 * the user callback, then the wait() call can be restarted.
 * 
 * @property-read Mutex $mutex Get mutex object.
 * @property-read boolean $expired Wait on condition expired.
 * @property-read mixed $result The callback result.
 * 
 * @property int $ttl The resource lock TTL.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Conditional
{

        /**
         * Default TTL for resource lock.
         */
        const LOCK_TTL = 86400;

        /**
         * The mutex lock.
         * @var Mutex 
         */
        private $_mutex;
        /**
         * Wait on condition expired.
         * @var boolean 
         */
        private $_expired;
        /**
         * The callback result.
         * @var mixed 
         */
        private $_result;
        /**
         * The resource lock TTL. 
         * @var int 
         */
        private $_ttl = self::LOCK_TTL;

        /**
         * Constructor.
         * @param Mutex $mutex The mutex lock.
         */
        public function __construct($mutex)
        {
                $this->_mutex = $mutex;
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'mutex':
                                return $this->_mutex;
                        case 'expired':
                                return $this->_expired;
                        case 'result':
                                return $this->_result;
                        case 'ttl':
                                return $this->_ttl;
                }
        }

        public function __set($name, $value)
        {
                switch ($name) {
                        case 'ttl':
                                $this->_ttl = (int) $value;
                }
        }

        /**
         * Wait for resource to become available.
         * 
         * @param string $resource The resource name.
         * @param callable $callback The callback function.
         * @param int $timeout The timeout in seconds (0 for infinite blocking).
         */
        public function wait($resource, $callback, $timeout = 0)
        {
                $this->_expired = false;
                $this->_result = false;

                if ($timeout == 0) {
                        $this->infinite($resource, $callback);
                } else {
                        $this->expiring($resource, $callback, $timeout);
                }
        }

        /**
         * Conditional wait with timeout.
         * 
         * @param string $resource The resource name.
         * @param callable $callback The callback function.
         * @param int $timeout The timeout in seconds (0 for infinite blocking).
         */
        private function expiring($resource, $callback, $timeout)
        {
                $expires = time() + $timeout;

                while (true) {
                        if ($this->calling($resource, $callback) !== false) {
                                return;
                        }
                        if ($expires < time()) {
                                $this->_expired = true;
                                return;
                        } else {
                                usleep($this->_mutex->delay);
                        }
                }
        }

        /**
         * Conditional wait infinite.
         * 
         * @param string $resource The resource name.
         * @param callable $callback The callback function.
         */
        private function infinite($resource, $callback)
        {
                while (true) {
                        if ($this->calling($resource, $callback) !== false) {
                                return;
                        } else {
                                usleep($this->_mutex->delay);
                        }
                }
        }

        /**
         * Try call once.
         * 
         * @param string $resource The resource name.
         * @param callable $callback The callback function.
         * 
         * @return boolean
         */
        private function calling($resource, $callback)
        {
                $result = $this->_mutex->open($resource, $callback, $this->_ttl);

                if ($result === false) {
                        return false;
                }

                $this->_expired = false;
                $this->_result = $result;

                return true;
        }

}
