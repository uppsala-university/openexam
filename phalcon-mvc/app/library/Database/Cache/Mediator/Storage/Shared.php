<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Shared.php
// Created: 2017-10-20 16:42:27
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator\Storage;

/**
 * Shared cache key storage.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Shared
{

        /**
         * The shared key storage.
         * @var array 
         */
        private static $_keys = array();

        /**
         * Add key to store.
         * @param string $key The key name.
         */
        public function add($key)
        {
                self::$_keys[$key] = true;
        }

        /**
         * Remove key from store.
         * @param string $key The key name.
         */
        public function remove($key)
        {
                unset(self::$_keys[$key]);
        }

        /**
         * Check if key exist in store.
         * @param string $key The key name.
         * @return bool
         */
        public function exist($key)
        {
                return isset(self::$_keys[$key]);
        }

        /**
         * Reset store by removing all keys.
         */
        public function reset()
        {
                self::$_keys = array();
        }

        /**
         * Get all keys.
         * @return array
         */
        public function keys()
        {
                return array_keys(self::$_keys);
        }

        /**
         * Get number of keys in store.
         * @return int
         */
        public function count()
        {
                return count(self::$_keys);
        }

}
