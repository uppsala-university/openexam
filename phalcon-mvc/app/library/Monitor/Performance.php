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
// File:    Performance.php
// Created: 2016-05-22 20:23:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor;

use OpenExam\Library\Monitor\Performance\Counter;
use OpenExam\Library\Monitor\Performance\Counter\Apache as ApachePerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Disk as DiskPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\FileSystem as FileSystemPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\MySQL as MySQLPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Network as NetworkPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Partition as PartitionPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Server as ServerPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Test as TestPerformanceCounter;
use Phalcon\Config;
use Phalcon\Mvc\User\Component;

/**
 * System performance monitoring.
 * 
 * This class is the unified frontend against performance counters. The counters 
 * represents various type of performance data collected by the performance
 * collectors (runned by the performance task).
 * 
 * This class uses the system config to maintain the registry of performance
 * counters that should be exposed. The counter data itself is stored in the 
 * performance model.
 * 
 * Performs search against performance model. The filter can be used to 
 * zoom in on data:
 * 
 * <code>
 * // 
 * // Give us last 30 records collected from www.example.com:
 * // 
 * $performance = new Performance(30);
 * $performance->setFilter(array(
 *      'host' => 'www.example.com'
 * ));
 * 
 * // 
 * // Get collected disk statistics:
 * // 
 * $counter = $performance->getCounter('disk');
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Performance extends Component
{

        /**
         * The performance monitor config.
         * @var Config 
         */
        private $_config;
        /**
         * Number of objects to return.
         * @var int 
         */
        private $_limits;
        /**
         * Search filter.
         * @var array 
         */
        private $_filter;
        /**
         * The available counters.
         * @var array 
         */
        private $_counters = array();

        /**
         * Constructor.
         * @param int $limit Number of objects to return.
         * @param type $filter The search filter.
         */
        public function __construct($limit = 20, $filter = array())
        {
                $this->_limits = $limit;
                $this->_filter = $filter;

                $this->_config = new Config();

                if ($this->_config->hasConfig('server')) {
                        $this->register('server', ServerPerformanceCounter::class);
                }
                if ($this->_config->hasConfig('disk')) {
                        $this->register('disk', DiskPerformanceCounter::class);
                }
                if ($this->_config->hasConfig('part')) {
                        $this->register('part', PartitionPerformanceCounter::class);
                }
//                if ($this->_config->hasConfig('system')) {
//                        $this->register('system', SystemPerformanceCounter::class);
//                }
                if ($this->_config->hasConfig('apache')) {
                        $this->register('apache', ApachePerformanceCounter::class);
                }
                if ($this->_config->hasConfig('mysql')) {
                        $this->register('mysql', MySQLPerformanceCounter::class);
                }
                if ($this->_config->hasConfig('net')) {
                        $this->register('net', NetworkPerformanceCounter::class);
                }
                if ($this->_config->hasConfig('fs')) {
                        $this->register('fs', FileSystemPerformanceCounter::class);
                }
                if ($this->_config->hasConfig('test')) {
                        $this->register('test', TestPerformanceCounter::class);
                }
        }

        /**
         * Set limit on returned records.
         * @param int $limit Number of records.
         */
        public function setLimit($limit)
        {
                $this->_limits = $limit;
        }

        /**
         * Get limit on returned records.
         * @return int
         */
        public function getLimits()
        {
                return $this->_limits;
        }

        /**
         * Get performance counter source (e.g. eth0).
         * @return string
         */
        public function getSource()
        {
                if (isset($this->_filter['source'])) {
                        return $this->_filter['source'];
                }
        }

        /**
         * Get monitor configuration object.
         * @return Config
         */
        public function getConfig()
        {
                return $this->_config;
        }

        /**
         * Set query filter.
         * 
         * <code>
         * $filter = array(
         *      'time' => '2016-05-24',
         *      'host' => 'server.example.com'
         * )
         * </code>
         * 
         * @param array $filter The filter to apply.
         */
        public function setFilter($filter)
        {
                $this->_filter = $filter;
        }

        /**
         * Get query filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_filter;
        }

        /**
         * Add filter option (replaces previous setting).
         * 
         * @param string $key The filter key (e.g. time).
         * @param string|int $val The filter value.
         */
        public function addFilter($key, $val)
        {
                $this->_filter[$key] = $val;
        }

        /**
         * Check if counter exist.
         * 
         * The type argument has to be one of the MODE_XXX constants defined 
         * by the performance model.
         * 
         * @param string $type The counter name.
         * @return boolean
         */
        public function hasCounter($type)
        {
                return isset($this->_counters[$type]);
        }

        /**
         * Get array of all counters.
         * 
         * <code>
         * array(
         *      'disk'   => Counter,
         *      'server' => Counter,
         *       ...
         * )
         * </code>
         * @return Counter[]
         */
        public function getCounters()
        {
                $counters = array();

                foreach (array_keys($this->_counters) as $type) {
                        $counters[$type] = $this->getCounter($type);
                }

                return $counters;
        }

        /**
         * Get performance counter.
         * 
         * The type argument has to be one of the MODE_XXX constants defined 
         * by the performance model. By default, performance data will be
         * returned for same server as invoking this method.
         * 
         * The counter returned keeps an reference to this performance object 
         * so filter can be dynamic modified later:
         * 
         * <code>
         * $performance = new Performance();
         * $counter = $performance->getCounter('disk');
         * 
         * $performance->addFilter('addr', '192.168.1.2');      // Override
         * $data = $counter->getData();
         * </code>
         * 
         * @param string $type The counter name.
         * @return Counter|boolean
         */
        public function getCounter($type)
        {
                if (!isset($this->_counters[$type])) {
                        return false;
                }
                if (!$this->_counters[$type]['inst']) {
                        $this->_counters[$type]['inst'] = new $this->_counters[$type]['type']($this);
                }

                return $this->_counters[$type]['inst'];
        }

        /**
         * Register performance counter.
         * @param string $name The performance counter name.
         * @param string $type The type name (class).
         */
        public function register($name, $type)
        {
                $this->_counters[$name] = array(
                        'type' => $type,
                        'inst' => false
                );
        }

}
