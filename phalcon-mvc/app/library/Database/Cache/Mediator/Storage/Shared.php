<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
