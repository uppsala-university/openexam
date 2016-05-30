<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
use OpenExam\Library\Monitor\Performance\Counter\Partition as PartitionPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\Server as ServerPerformanceCounter;
use OpenExam\Library\Monitor\Performance\Counter\MySQL as MySQLPerformanceCounter;
use Phalcon\Mvc\User\Component;

/**
 * System performance diagnostics.
 * 
 * Performs search against performance model. The filter can be used to 
 * zoom in on data:
 * 
 * <code>
 * // 
 * // Get virtual memory counter for this week:
 * // 
 * $performance = new Performance();
 * $performance->setFilter(array(
 *      'milestone' => 'week'
 * ));
 * $counter = $performance->getCounter('disk');
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Performance extends Component
{

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

                if ($this->config->monitor->get('server')) {
                        $this->register('server', ServerPerformanceCounter::class);
                }
                if ($this->config->monitor->get('disk')) {
                        $this->register('disk', DiskPerformanceCounter::class);
                }
                if ($this->config->monitor->get('part')) {
                        $this->register('part', PartitionPerformanceCounter::class);
                }
//                if ($this->config->monitor->get('system')) {
//                        $this->register('system', SystemPerformanceCounter::class);
//                }
                if ($this->config->monitor->get('apache')) {
                        $this->register('apache', ApachePerformanceCounter::class);
                }
                if ($this->config->monitor->get('mysql')) {
                        $this->register('mysql', MySQLPerformanceCounter::class);
                }
//                if ($this->config->monitor->get('net')) {
//                        $this->register('net', NetworkPerformanceCounter::class);
//                }
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
         * Add filter option.
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
         * by the performance model.
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
