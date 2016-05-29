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
use OpenExam\Library\Monitor\Performance\Counter\Server\DiskStatisticsCounter;
use OpenExam\Library\Monitor\Performance\Counter\Server\PartitionStatisticsCounter;
use OpenExam\Library\Monitor\Performance\Counter\Server\VirtualMemoryCounter;
use OpenExam\Models\Performance as PerformanceModel;

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
class Performance
{

        /**
         * The system performance counter.
         */
        const COUNTER_SERVER = 'server';
        /**
         * The disk performance counter.
         */
        const COUNTER_DISK = 'disk';
        /**
         * The partition performance counter.
         */
        const COUNTER_PARTITION = 'partition';

        /**
         * Number of objects to return.
         * @var int 
         */
        private $_limit;
        /**
         * Search filter.
         * @var array 
         */
        private $_filter;

        /**
         * Constructor.
         * @param int $limit Number of objects to return.
         * @param type $filter The search filter.
         */
        public function __construct($limit = 20, $filter = array())
        {
                $this->_limit = $limit;
                $this->_filter = $filter;
        }

        /**
         * Set limit on returned records.
         * @param int $limit Number of records.
         */
        public function setLimit($limit)
        {
                $this->_limit = $limit;
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
         * @param string $type The counter name (one of COUNTER_XXX constants).
         * @return boolean
         */
        public function hasCounter($type)
        {
                return
                    $type == self::COUNTER_DISK ||
                    $type == self::COUNTER_PARTITION ||
                    $type == self::COUNTER_SERVER;
        }

        /**
         * Get performance counter.
         * @param string $type The counter name (one of COUNTER_XXX constants).
         * @return Counter
         */
        public function getCounter($type)
        {
                switch ($type) {
                        case self::COUNTER_DISK:
                                return new DiskStatisticsCounter($this->getData('disk'));
                        case self::COUNTER_PARTITION:
                                return new PartitionStatisticsCounter($this->getData('part'));
                        case self::COUNTER_SERVER:
                                return new VirtualMemoryCounter($this->getData('vm'));
                        default:
                                return false;
                }
        }

        /**
         * Get performance counter data.
         * 
         * @param string $mode The performance data type.
         * @return array
         * @throws Exception
         */
        public function getData($mode)
        {
                $filter = $this->_filter;
                $limits = $this->_limit;

                if (!isset($filter['addr']) && !isset($filter['host'])) {
                        $filter['addr'] = gethostbyname(gethostname());
                }
                if (isset($mode)) {
                        $filter['mode'] = $mode;
                }

                $conditions = array();

                foreach ($filter as $key => $val) {
                        if ($key == 'time') {
                                $conditions[] = "$key LIKE \"$val%\"";
                                unset($filter['time']);
                        } else {
                                $conditions[] = "$key = :$key:";
                        }
                }

                if (($result = PerformanceModel::find(array(
                            'conditions' => implode(" AND ", $conditions),
                            'bind'       => $filter,
                            'order'      => 'time DESC',
                            'limit'      => $limits
                    )))) {
                        $data = array();
                        foreach ($result as $model) {
                                $data[] = $model->toArray();
                        }
                        return array_reverse($data);
                } else {
                        throw new Exception("Failed query performance model");
                }
        }

}
