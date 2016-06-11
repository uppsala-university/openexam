<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Cleanup.php
// Created: 2016-06-09 04:07:05
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Trigger;

use OpenExam\Library\Monitor\Performance\Trigger;
use OpenExam\Models\Performance;

/**
 * Performance records cleanup.
 * 
 * This trigger trims the performance table by deleting records older that
 * specified number of seconds. The 'live' option refers to live data (not
 * an milestone). Any milestone type might be used.
 * 
 * Example:
 * <code>
 * // 
 * // Keep live data for 3 min and minute milestones for 1 hour:
 * // 
 * $trigger = new Cleanup(array(
 *      'live'   => 180, 
 *      'minute' => 3600
 * ));
 * $trigger->process($performance);     // Process performance model object.
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Cleanup implements Trigger
{

        /**
         * Suggested lifetime for live data (three minutes).
         */
        const LIVE = 180;
        /**
         * Suggested lifetime for minute milestones (one hour).
         */
        const MINUTE = 3600;
        /**
         * Suggested lifetime for hourly milestones (one week).
         */
        const HOUR = 604800;
        /**
         * Suggested lifetime for daily milestones (one month).
         */
        const DAY = 2678400;
        /**
         * Suggested lifetime for weekly milestones (one year).
         */
        const WEEK = 31536000;

        /**
         * Lifetime for live data (UNIX timestamp).
         * @var int 
         */
        private $_live = false;
        /**
         * Lifetime for minute milestones (UNIX timestamp).
         * @var int 
         */
        private $_minute = false;
        /**
         * Lifetime for hourly milestones (UNIX timestamp).
         * @var int 
         */
        private $_hour = false;
        /**
         * Lifetime for daily milestones (UNIX timestamp).
         * @var int 
         */
        private $_day = false;
        /**
         * Lifetime for weekly milestones (UNIX timestamp).
         * @var int 
         */
        private $_week = false;
        /**
         * The counter type (e.g. disk).
         * @var string 
         */
        private $_type;
        /**
         * Last garbage collection.
         * @var array 
         */
        private $_gc = array();

        /**
         * Constructor.
         * 
         * @param string $type The performance counter type.
         * @param array $options The options array.
         */
        public function __construct($type, $options = array(
                'live'   => 180,
                'minute' => 3600,
                'hour'   => 604800,
                'day'    => 2678400,
                'week'   => 31536000
        ))
        {
                $this->_type = $type;

                if (isset($options)) {
                        if ($options['live']) {
                                $this->_live = $options['live'];
                        }
                        if ($options['minute']) {
                                $this->_minute = $options['minute'];
                        }
                        if ($options['hour']) {
                                $this->_hour = $options['hour'];
                        }
                        if ($options['day']) {
                                $this->_day = $options['day'];
                        }
                        if ($options['week']) {
                                $this->_week = $options['week'];
                        }
                }

                $this->_gc = array(
                        'live'   => time(),
                        'minute' => time(),
                        'hour'   => time(),
                        'day'    => time(),
                        'week'   => time()
                );
        }

        /**
         * Process the performance model object.
         * @param Performance $model
         */
        public function process($model)
        {
                $time = time();

                foreach (array_keys($this->_gc) as $type) {
                        if (!$this->expired($time, $type)) {
                                continue;
                        }
                        if ($this->cleanup($model, $this->_gc[$type], $type)) {
                                $this->_gc[$type] = $time;
                        }
                }
        }

        /**
         * Cleanup performance records.
         * 
         * @param Performance $model The performance model.
         * @param int $before Operate on records previous to time stamp.
         * @param string $type The milestone type or live.
         * @return boolean True if successful.
         */
        private function cleanup($model, $before, $type)
        {
                $time = strftime(self::DATE_FORMAT, $before);

                // 
                // Set required parameters:
                // 
                $query = Performance::query()
                    ->andWhere("time <= :time:", array('time' => $time))
                    ->andWhere("mode = :mode:", array('mode' => $model->mode))
                    ->andWhere("addr = :addr:", array('addr' => $model->addr));

                // 
                // Append source if defined:
                // 
                if (isset($model->source)) {
                        $query->andWhere('source = :source:', array(
                                'source' => $model->source
                        ));
                }

                // 
                // Select milestones unless working on live data.
                // 
                if ($type == 'live') {
                        $query->andWhere('milestone IS NULL');
                } else {
                        $query->andWhere('milestone = :type:', array('type' => $type));
                }

                // 
                // Return true if records were deleted or if there nothing to delete.
                // 
                if (!($result = $query->execute())) {
                        return false;
                } elseif (count($result) == 0) {
                        return true;
                } elseif ($result->delete() != 0) {
                        return true;
                } else {
                        return false;
                }
        }

        /**
         * Check if garbage collection should be done.
         * 
         * @param int $time The UNIX timestamp.
         * @param string $type The milestpne type or live.
         * @return boolean
         */
        private function expired($time, $type)
        {
                switch ($type) {
                        case 'live':
                                if ($this->_live == false) {
                                        return false;
                                } else {
                                        return ($time - $this->_live) > $this->_gc['live'];
                                }
                        case 'minute':
                                if ($this->_minute == false) {
                                        return false;
                                } else {
                                        return ($time - $this->_minute) > $this->_gc['minute'];
                                }
                        case 'hour':
                                if ($this->_hour == false) {
                                        return false;
                                } else {
                                        return ($time - $this->_hour) > $this->_gc['hour'];
                                }
                        case 'day':
                                if ($this->_day == false) {
                                        return false;
                                } else {
                                        return ($time - $this->_day) > $this->_gc['day'];
                                }
                        case 'week':
                                if ($this->_week == false) {
                                        return false;
                                } else {
                                        return ($time - $this->_week) > $this->_gc['week'];
                                }
                }
        }

}
