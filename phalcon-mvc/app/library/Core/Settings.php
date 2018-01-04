<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    Settings.php
// Created: 2015-04-08 09:24:27
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

use OpenExam\Library\Model\Exception;
use OpenExam\Models\Setting;
use Phalcon\Mvc\User\Component;

/**
 * User settings object.
 * 
 * This class implements dirty state for the settings model providing
 * automatic saving on destruction if the user settings has been modified
 * by calling set() on this object.
 * 
 * It also support manual flush to persistent storage using save() and
 * reload from persistent storage (state reset) using the read() method.
 * 
 * The KEY_XXX constant defines some common keys for settings entries.
 * 
 * @see Setting
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Settings extends Component
{

        /**
         * The language/locale preferences.
         */
        const KEY_LANG = 'lang';
        /**
         * Most recent used (MRU).
         */
        const KEY_RECENT = 'mru';
        /**
         * UI details level.
         */
        const KEY_DETAILS = 'details';
        /**
         * Last used access list.
         */
        const KEY_ACCESS = 'access';

        /**
         * The user principal.
         * @var string 
         */
        private $_principal;
        /**
         * Dirty flag.
         * @var boolean 
         */
        private $_dirty = false;
        /**
         * The setting model.
         * @var Setting 
         */
        private $_settings;

        /**
         * Constructor.
         * @param string $principal
         */
        public function __construct($principal = null)
        {
                if (isset($principal)) {
                        $this->_principal = $principal;
                } else {
                        $this->_principal = $this->user->getPrincipalName();
                }
                $this->read();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                $this->save();
        }

        /**
         * Set an key/value pair, optional in a sub section.
         * 
         * The settings value can be anything that can be serialized, but 
         * is usually a string or array. If $sect is unset, then the value
         * is set in the first dimension of the data property having the 
         * $key parameter as its associative key.
         * 
         * <code>
         * $settings->set('key1', 'val1');
         * $settings->set('key1', 'val1', 'sect1');
         * </code>
         * 
         * It's also possible to set all settings at once by passing an array
         * as the key parameter:
         * 
         * <code>
         * $settings->set(array('key1' => 'val1', 'key2' => ...));
         * </code>
         * 
         * @param string|array $key The settings key name.
         * @param string|array $val The settings value.
         * @param string $sect Optional sub section.
         */
        public function set($key, $val = null, $sect = null)
        {
                $this->_dirty = true;
                $this->_settings->set($key, $val, $sect);
        }

        /**
         * Get value of settings key, optional from a sub section.
         * 
         * <code>
         * $value = $settings->get('key1');
         * $value = $settings->get('key1', 'sect1');
         * </code>
         * 
         * Return null if key or (optional) sect is not set.
         * 
         * @param string $key The settings key name.
         * @param string $sect Optinal sub section.
         * @return string|array
         */
        public function get($key, $sect = null)
        {
                return $this->_settings->get($key, $sect);
        }

        /**
         * Check if key exist.
         * @param string $key The settings key name.
         * @param string $sect Optinal sub section.
         * @return boolean
         */
        public function has($key, $sect = null)
        {
                return $this->_settings->has($key, $sect);
        }

        /**
         * Save settings to persistent storage.
         * @throws Exception
         */
        public function save()
        {
                if ($this->_dirty) {
                        if ($this->_settings->save() == false) {
                                throw new Exception($this->_settings->getMessages()[0]);
                        }
                }

                $this->_dirty = false;
        }

        /**
         * Read settings from persistent storage.
         */
        public function read()
        {
                if (($this->_settings = Setting::findFirstByUser($this->_principal)) == false) {
                        $this->_settings = new Setting();
                        $this->_settings->user = $this->_principal;
                }

                $this->_dirty = false;
        }

        /**
         * Clear all user settings.
         */
        public function clear()
        {
                $this->_settings->data = array();
                $this->_settings->save();
                $this->_dirty = false;
        }

        /**
         * Delete user settings.
         * @throws Exception
         */
        public function delete()
        {
                if ($this->_settings->delete() == false) {
                        throw new Exception($this->_settings->getMessages()[0]);
                }

                $this->_dirty = false;
        }

        /**
         * Return model data.
         * @return array
         */
        public function data()
        {
                return $this->_settings->data;
        }

}
