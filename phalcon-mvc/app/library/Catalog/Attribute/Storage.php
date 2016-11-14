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
use OpenExam\Models\User;
use Phalcon\Mvc\User\Component;

/**
 * User attribute storage.
 * 
 * Provides a service for storing users into different storage backend using
 * the domain as selector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Storage extends Component implements Backend
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
         * Add storage backend for domain.
         * 
         * Use domain = '*' to add a default storage backend for unmatched
         * domains.
         * 
         * @param string $domain The domain name.
         * @param Backend $backend The storage backend.
         */
        public function addBackend($domain, $backend)
        {
                $this->_backends[$domain] = $backend;
        }

        /**
         * Get storage backend for user principal.
         * @param string $principal The user principal name.
         */
        public function getBackend($principal)
        {
                if (strstr($principal, '@')) {
                        list($user, $domain) = explode('@', $principal);
                } else {
                        list($user, $domain) = array($principal, '*');
                }

                if (array_key_exists($domain, $this->_backends)) {
                        return $this->_backends[$domain];
                } elseif (array_key_exists('*', $this->_backends)) {
                        return $this->_backends['*'];
                } else {
                        return $this->_backends['-'];
                }
        }

        /**
         * Check if storage backend exists.
         * @param string $principal The user principal.
         */
        public function hasBackend($principal)
        {
                if (strstr($principal, '@')) {
                        list($user, $domain) = explode('@', $principal);
                } else {
                        list($user, $domain) = array($principal, '*');
                }

                return array_key_exists($domain, $this->_backends);
        }

        /**
         * Check if user exist.
         * @param string $principal The user principal name.
         * @return boolean 
         */
        public function exists($principal)
        {
                $backend = $this->getBackend($principal);
                return $backend->exists($principal);
        }

        /**
         * Insert user attributes.
         * @param User $user The user model.
         */
        public function insert($user)
        {
                $backend = $this->getBackend($user->principal);
                $backend->insert($user);
        }

        /**
         * Delete user.
         * @param string $principal The user principal name.
         */
        public function delete($principal)
        {
                $backend = $this->getBackend($principal);
                $backend->delete($principal);
        }

}
