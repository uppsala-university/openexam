<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Storage.php
// Created: 2016-11-13 14:08:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute;

use OpenExam\Library\Catalog\Attribute\Storage\Backend;
use OpenExam\Library\Catalog\Attribute\Storage\Bucket;
use Phalcon\Mvc\User\Component;

/**
 * User attribute storage.
 * 
 * Provides a service for storing users into different storage backend using
 * the domain as selector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Storage extends Component
{

        /**
         * The array of backends.
         * @var array 
         */
        private $_backends = array();

        /**
         * Constructor.
         * @param array $backends The array of backends.
         */
        public function __construct($backends = array())
        {
                $this->_backends = $backends;
                $this->_backends['-'] = new Bucket();
        }

        /**
         * Add storage backend for method.
         * 
         * @param string $method The logon method (i.e. cas).
         * @param Backend $backend The storage backend.
         */
        public function addBackend($method, $backend)
        {
                $this->_backends[$method] = $backend;
        }

        /**
         * Get storage backend for logon method.
         * @param string $method The logon method (i.e. cas).
         * @return Backend 
         */
        public function getBackend($method)
        {
                if (array_key_exists($method, $this->_backends)) {
                        return $this->_backends[$method];
                } elseif (array_key_exists('*', $this->_backends)) {
                        return $this->_backends['*'];
                } else {
                        return $this->_backends['-'];
                }
        }

        /**
         * Check if storage backend exists.
         * @param string $method The logon method (i.e. cas).
         * @return boolean 
         */
        public function hasBackend($method)
        {
                return (
                    array_key_exists($method, $this->_backends) ||
                    array_key_exists('*', $this->_backends)
                    );
        }

        /**
         * Get all backends.
         * @return array
         */
        public function getBackends()
        {
                return $this->_backends;
        }

}
