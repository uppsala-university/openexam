<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        private $principal;
        /**
         * Dirty flag.
         * @var boolean 
         */
        private $dirty = false;
        /**
         * The setting model.
         * @var Setting 
         */
        private $settings;

        /**
         * Constructor.
         * @param string $principal
         */
        public function __construct($principal = null)
        {
                if (isset($principal)) {
                        $this->principal = $principal;
                } else {
                        $this->principal = $this->user->getPrincipalName();
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
                $this->dirty = true;
                $this->settings->set($key, $val, $sect);
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
                return $this->settings->get($key, $sect);
        }

        /**
         * Check if key exist.
         * @param string $key The settings key name.
         * @param string $sect Optinal sub section.
         * @return boolean
         */
        public function has($key, $sect = null)
        {
                return $this->settings->has($key, $sect);
        }

        /**
         * Save settings to persistent storage.
         * @throws Exception
         */
        public function save()
        {
                if ($this->dirty) {
                        if ($this->settings->save() == false) {
                                throw new Exception($this->settings->getMessages()[0]);
                        }
                }
                
                $this->dirty = false;
        }

        /**
         * Read settings from persistent storage.
         */
        public function read()
        {
                if (($this->settings = Setting::findFirstByUser($this->principal)) == false) {
                        $this->settings = new Setting();
                        $this->settings->user = $this->principal;
                }
                
                $this->dirty = false;
        }

        /**
         * Clear all user settings.
         */
        public function clear()
        {
                $this->settings->data = array();
                $this->settings->save();
                $this->dirty = false;
        }

        /**
         * Delete user settings.
         * @throws Exception
         */
        public function delete()
        {
                if ($this->settings->delete() == false) {
                        throw new Exception($this->settings->getMessages()[0]);
                }
                
                $this->dirty = false;
        }

        /**
         * Return model data.
         * @return array
         */
        public function data()
        {
                return $this->settings->data;
        }

}
