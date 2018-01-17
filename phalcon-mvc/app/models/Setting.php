<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    Setting.php
// Created: 2014-11-29 23:54:14
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Generate\Ownership;

/**
 * User settings.
 * 
 * Provides a property bag of user settings. The settings can ungrouped or
 * placed in a sub section:
 * 
 * <code>
 * $data = array(
 *      'k1' => 'v1',           // $settings->set('k1', 'v1');
 *      'k2' => 'v2',           // $settings->set('k2', 'v2');
 *      's1' => array(
 *              'p1' => 'v1',   // $settings->set('p1', 'v1', 's1');
 *              'p2' => 'v2'    // $settings->set('p2', 'v2', 's1');
 *      ),
 *      's2' => array(
 *              'p3' => 'v3', 
 *                ...
 *        ...
 * );
 * </code>
 * 
 * Notice: The user property is always set to calling user when the model
 * object is persisted to storage.
 */
class Setting extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;
        /**
         * The property bag.
         * @var array
         */
        public $data = array();

        public function initialize()
        {
                parent::initialize();

                $this->addBehavior(new Ownership(array(
                        'beforeValidationOnCreate' => array(
                                'field' => 'user',
                                'force' => true
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => 'user',
                                'force' => true
                        )
                )));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'settings';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'   => 'id',
                        'user' => 'user',
                        'data' => 'data'
                );
        }

        public function beforeSave()
        {
                $this->data = serialize($this->data);
        }

        public function afterSave()
        {
                $this->data = unserialize($this->data);
        }

        public function afterFetch()
        {
                $this->data = unserialize($this->data);
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
                if (!isset($val)) {
                        $this->data = $key;
                } elseif (!isset($sect)) {
                        $this->data[$key] = $val;
                } else {
                        if (!isset($this->data[$sect])) {
                                $this->data[$sect] = array();
                        }
                        $this->data[$sect][$key] = $val;
                }
        }

        /**
         * Get value of settings key, optional from a sub section.
         * 
         * <code>
         * $value = $settings->get('key1');
         * $value = $settings->get('key1', 'sect1');
         * </code>
         * 
         * @param string $key The settings key name.
         * @param string $sect Optinal sub section.
         * @return string|array
         */
        public function get($key, $sect = null)
        {
                if ($this->has($key, $sect) == false) {
                        return null;
                }
                if (isset($sect)) {
                        return $this->data[$sect][$key];
                } else {
                        return $this->data[$key];
                }
        }

        /**
         * Check if key is set, optional from a sub section.
         * @param string $key The settings key name.
         * @param string $sect Optinal sub section.
         * @return boolean
         */
        public function has($key, $sect = null)
        {
                if (isset($sect)) {
                        return isset($this->data[$sect][$key]);
                } else {
                        return isset($this->data[$key]);
                }
        }

}
