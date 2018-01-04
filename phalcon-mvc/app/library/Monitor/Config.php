<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Config.php
// Created: 2016-06-10 00:39:34
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor;

use Phalcon\Config as PhalconConfig;
use Phalcon\Mvc\User\Component;

/**
 * Performance monitor config.
 * 
 * This class provides an easier interface to the user defined monitor configuration.
 * The counter names are the same as the MODE_XXX constants defined by the performance
 * model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Config extends Component
{

        /**
         * Parsed monitor config options.
         * @var array 
         */
        private $_config;
        /**
         * Default options for triggers.
         * @var array 
         */
        private $_trigger;

        /**
         * Constructor.
         * @param array $config Optional config.
         */
        public function __construct($config = null)
        {
                if (isset($config)) {
                        $this->parse($config);
                } else {
                        $this->parse($this->config->monitor);
                }
        }

        /**
         * Check if counters exists.
         * @return boolean
         */
        public function hasCounters()
        {
                return count($this->_config) != 0;
        }

        /**
         * Get array of counters.
         * @return array
         */
        public function getCounters()
        {
                return array_keys($this->_config);
        }

        /**
         * Get params for this counter.
         * 
         * @param string $counter The counter name.
         * @return boolean|array
         */
        public function getParams($counter)
        {
                if (isset($this->_config[$counter]['params'])) {
                        return $this->_config[$counter]['params'];
                }
        }

        /**
         * Get triggers for this counter.
         * 
         * @param string $counter The counter name.
         * @return boolean|array
         */
        public function getTriggers($counter)
        {
                if (isset($this->_config[$counter]['triggers'])) {
                        return $this->_config[$counter]['triggers'];
                }
        }

        /**
         * Check if counter has config (enabled).
         * @param string $counter The counter name.
         * @return boolean
         */
        public function hasConfig($counter)
        {
                return isset($this->_config[$counter]);
        }

        /**
         * Get parsed config.
         * @return array
         */
        public function getConfig($counter = null)
        {
                if (isset($counter) && $counter) {
                        return $this->_config[$counter];
                } else {
                        return $this->_config;
                }
        }

        /**
         * Parse given config.
         * @param PhalconConfig $config The monitor config.
         */
        private function parse($config)
        {
                // 
                // Return immediate if monitor is disabled.
                // 
                if ($config == false) {
                        $this->_config = array();
                        return;
                }

                // 
                // It's easier to parse options as array.
                // 
                if (is_object($config)) {
                        $config = $config->toArray();
                }


                // 
                // Set default options for triggers:
                // 
                if (isset($config['trigger'])) {
                        $this->_trigger = $config['trigger'];
                } else {
                        $this->_trigger = array(
                                'timeline' => array(),
                                'cleanup'  => array()
                        );
                }

                // 
                // Insert standard set of counters:
                // 
                if (!isset($config['counter'])) {
                        $this->addCounter('server');
                        $this->addCounter('apache');
                        $this->addCounter('mysql');
                        $this->addCounter('net');
                        $this->addCounter('fs');
                        return;
                }

                // 
                // Parse counters section. Each counter is assigned params and
                // triggers under influence of triggers config.
                // 
                foreach ($config['counter'] as $key => $val) {
                        if ($key == false) {
                                continue;       // Skip this counter.
                        } else {
                                $this->addCounter($key);
                        }
                        if (isset($val['params'])) {
                                $this->setParams($key, $val['params']);
                        }
                        if (isset($val['trigger'])) {
                                $this->setTriggers($key, $val['trigger']);
                        }
                }
        }

        /**
         * Add counter.
         * @param string $name The counter name.
         */
        private function addCounter($name)
        {
                $this->_config[$name] = array();
                $this->setParams($name, array());
                $this->addTriggers($name);
        }

        /**
         * Add trigger for counter.
         * 
         * @param string $counter The counter name.
         * @param string $trigger The trigger name.
         * @param array $options Optional triggers params.
         */
        private function addTrigger($counter, $trigger, $options = null)
        {
                if (!isset($options)) {
                        $options = $this->_trigger[$trigger];
                }

                if (is_array($options)) {
                        $this->_config[$counter]['triggers'][$trigger] = $options;
                } elseif ($options === true) {
                        $this->_config[$counter]['triggers'][$trigger] = $options;
                } elseif ($options === false) {
                        unset($this->_config[$counter]['triggers'][$trigger]);
                }
        }

        /**
         * Add default set of triggers.
         * @param string $counter The counter name.
         */
        private function addTriggers($counter)
        {
                foreach ($this->_trigger as $trigger => $options) {
                        $this->addTrigger($counter, $trigger, $options);
                }
        }

        /**
         * Set params for this counter.
         * 
         * <code>
         * $this->setParams('apache', array('rate' => 5, 'user' => 'www'));
         * </code>
         * 
         * @param string $counter The counter name.
         * @param array $params The counter params.
         */
        private function setParams($counter, $params)
        {
                $this->_config[$counter]['params'] = $params;
        }

        /**
         * Set triggers for counter.
         * @param string $counter The counter name.
         * @param array $triggers Array of triggers to associate with counter.
         */
        private function setTriggers($counter, $triggers)
        {
                foreach ($triggers as $key => $val) {
                        if ($val == false) {
                                if (isset($this->_config[$counter]['triggers'][$key])) {
                                        unset($this->_config[$counter]['triggers'][$key]);
                                }
                        } else {
                                $this->addTrigger($counter, $key, $val);
                        }
                }
        }

}
