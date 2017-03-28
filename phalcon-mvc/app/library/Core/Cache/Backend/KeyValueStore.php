<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    HashKeyValue.php
// Created: 2017-03-28 14:34:42
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Cache\Backend;

use Phalcon\Cache\BackendInterface;

/**
 * Hash key/value cache backend.
 * 
 * Provides a hashed key/value store with a cache backend as fallback. Use
 * this class as an intermediate store of values fetched from the backend
 * cache. The values are delivered as is from this store if found, thus
 * by-passing the usual serialization by frontends.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class KeyValueStore
{

        /**
         * The backend cache.
         * @var BackendInterface 
         */
        private $_backend;
        /**
         * The key/value store.
         * @var array 
         */
        private $_store = array();

        /**
         * Constructor.
         * 
         * @param BackendInterface $backend The backend cache.
         */
        public function __construct($backend)
        {
                $this->_backend = $backend;
        }

        /**
         * Delete key from cache.
         * 
         * @param string $keyName The key name.
         */
        public function delete($keyName)
        {
                if (isset($this->_store[$keyName])) {
                        unset($this->_store[$keyName]);
                }
                $this->_backend->delete($keyName);
        }

        /**
         * Check if key exists.
         * 
         * @param string $keyName The key name.
         * @param type $lifetime
         * @return boolean
         */
        public function exists($keyName = null, $lifetime = null)
        {
                if (isset($this->_store[$keyName])) {
                        return true;
                } else {
                        return $this->_backend->exists($keyName, $lifetime);
                }
        }

        /**
         * Get key value.
         * 
         * @param string $keyName The key name.
         * @param int $lifetime The TTL for the key.
         * @return mixed|null
         */
        public function get($keyName, $lifetime = null)
        {
                if (isset($this->_store[$keyName])) {
                        return $this->_store[$keyName];
                }

                $content = $this->_backend->get($keyName, $lifetime);
                $this->_store[$keyName] = $content;

                return $content;
        }

        /**
         * Save cache content.
         * 
         * @param string $keyName The key name.
         * @param string $content The content value.
         * @param int $lifetime The TTL for the key.
         * @param bool $stopBuffer Stop frontend buffer.
         */
        public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
        {
                $this->_backend->save($keyName, $content, $lifetime, $stopBuffer);
        }

        /**
         * Query all keys.
         * 
         * @param string $prefix The key prefix (optional).
         * @return array
         */
        public function queryKeys($prefix = null)
        {
                if (isset($prefix)) {
                        $length = strlen($prefix);
                        return array_filter(array_keys($this->_store), function($key) use($length, $prefix) {
                                return strncmp($key, $prefix, $length) == 0;
                        });
                } else {
                        return array_keys($this->_store);
                }
        }

}
