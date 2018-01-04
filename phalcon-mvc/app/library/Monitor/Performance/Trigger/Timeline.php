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
// File:    Timeline.php
// Created: 2016-06-09 03:48:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Trigger;

use OpenExam\Library\Monitor\Performance\Trigger;
use OpenExam\Models\Performance;
use Phalcon\Mvc\Model\ResultInterface;

/**
 * Timeline trigger for performance monitoring.
 * 
 * This trigger processes performance model feed from the collector and insert 
 * milestones records (minute, hour, day, week or month) into the performance 
 * table.
 * 
 * <code>
 * // 
 * // Only create milestones for hour and day switch.
 * // 
 * $trigger = new Timeline(array(
 *      Timeline::HOUR => true, 
 *      Timeline::DAY  => true
 * ));
 * $trigger->process($performance);     // Process performance model object.
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Timeline implements Trigger
{

        /**
         * The minute milestone.
         */
        const MINUTE = Performance::MILESTONE_MINUTE;
        /**
         * The hour milestone.
         */
        const HOUR = Performance::MILESTONE_HOUR;
        /**
         * The day milestone.
         */
        const DAY = Performance::MILESTONE_DAY;
        /**
         * The week milestone.
         */
        const WEEK = Performance::MILESTONE_WEEK;
        /**
         * The month milestone.
         */
        const MONTH = Performance::MILESTONE_MONTH;
        /**
         * The year milestone.
         */
        const YEAR = Performance::MILESTONE_YEAR;

        /**
         * Next milestones by minute (UNIX timestamp).
         * @var int
         */
        private $_minute = false;
        /**
         * Next milestones by hour (UNIX timestamp).
         * @var int
         */
        private $_hour = false;
        /**
         * Next milestones by day (UNIX timestamp).
         * @var int
         */
        private $_day = false;
        /**
         * Next milestones by week (UNIX timestamp).
         * @var int
         */
        private $_week = false;
        /**
         * Next milestones by month (UNIX timestamp).
         * @var int
         */
        private $_month = false;
        /**
         * Next milestones by year (UNIX timestamp).
         * @var int
         */
        private $_year = false;
        /**
         * The counter type (e.g. disk).
         * @var string 
         */
        private $_type;

        /**
         * Constructor.
         * 
         * @param string $type The performance counter type.
         * @param array $options The options array.
         */
        public function __construct($type, $options = array(
                self::MINUTE => true,
                self::MINUTE => true,
                self::HOUR   => true,
                self::DAY    => true,
                self::WEEK   => true,
                self::MONTH  => true,
                self::YEAR   => true
        ))
        {
                $this->_type = $type;

                if (isset($options)) {
                        if (isset($options[self::MINUTE])) {
                                $this->set(self::MINUTE);
                        }
                        if (isset($options[self::HOUR])) {
                                $this->set(self::HOUR);
                        }
                        if (isset($options[self::DAY])) {
                                $this->set(self::DAY);
                        }
                        if (isset($options[self::WEEK])) {
                                $this->set(self::WEEK);
                        }
                        if (isset($options[self::MONTH])) {
                                $this->set(self::MONTH);
                        }
                        if (isset($options[self::YEAR])) {
                                $this->set(self::YEAR);
                        }
                }
                
                ini_set('serialize_precision', 8);
        }

        /**
         * Process the performance model object.
         * @param Performance $model
         */
        public function process($model)
        {
                $time = strtotime($model->time);

                if ($time >= $this->_minute) {
                        $this->add($model, self::MINUTE);
                }
                if ($time >= $this->_hour) {
                        $this->add($model, self::HOUR);
                }
                if ($time >= $this->_day) {
                        $this->add($model, self::DAY);
                }
                if ($time >= $this->_week) {
                        $this->add($model, self::WEEK);
                }
                if ($time >= $this->_month) {
                        $this->add($model, self::MONTH);
                }
                if ($time >= $this->_year) {
                        $this->add($model, self::YEAR);
                }
        }

        /**
         * Add new milestone.
         * 
         * @param Performance $model The performance model.
         * @param string $name The milestone type (e.g. hour).
         */
        private function add($model, $name)
        {
                $prev = self::prev($name);
                $next = self::next($name);
                
                switch ($name) {
                        case self::MINUTE:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_minute = $next;
                                }
                                break;
                        case self::HOUR:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_hour = $next;
                                }
                                break;
                        case self::DAY:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_day = $next;
                                }
                                break;
                        case self::WEEK:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_week = $next;
                                }
                                break;
                        case self::MONTH:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_month = $next;
                                }
                                break;
                        case self::YEAR:
                                if ($this->insert($model, $name, $prev)) {
                                        $this->_year = $next;
                                }
                                break;
                }
        }

        /**
         * Insert avarage performance counter.
         * 
         * @param Performance $model The performance model.
         * @param string $name The milestone type (e.g. hour).
         * @param int $prev The UNIX timestamp.
         * @return boolean 
         */
        private function insert($model, $name, $prev)
        {
                if (!($result = self::find($model, $name, $prev))) {
                        return false;
                }
                if (!($data = self::average($result))) {
                        return false;
                }

                $perf = new Performance();

                $perf->data = $data;
                $perf->addr = $model->addr;
                $perf->host = $model->host;
                $perf->milestone = $name;
                $perf->mode = $model->mode;
                $perf->source = $model->source;

                if (!$perf->save()) {
                        foreach ($perf->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }
                
                return true;
        }

        /**
         * Get array of average values.
         * @param ResultInterface $result The result set.
         * @return array 
         */
        private static function average($result)
        {
                if (count($result) < 1) {
                        return false;
                }

                $first = unserialize($result[0]['data']);
                $count = count($result);

                foreach ($first as $c => $a) {
                        foreach ($a as $k => $v) {
                                $first[$c][$k] = 0;
                        }
                }

                foreach ($result as $data) {
                        $d = unserialize($data['data']);

                        foreach ($d as $c => $a) {
                                foreach ($a as $k => $v) {
                                        $first[$c][$k] += $v;
                                }
                        }
                }

                foreach ($first as $c => $a) {
                        foreach ($a as $k => $v) {
                                $first[$c][$k] = round($v / $count, 3);
                        }
                }
                
                return $first;
        }

        /**
         * Find resultset of anchestors to given milestone.
         * 
         * @param Performance $model The performance model.
         * @param string $name The milestone type (e.g. hour).
         * @param int $prev The UNIX timestamp.
         * @return ResultInterface 
         */
        private function find($model, $name, $prev)
        {
                $time = strftime(self::DATE_FORMAT, $prev);

                // 
                // Create query of required parameters:
                // 
                $query = Performance::query()
                    ->columns("data")
                    ->andWhere("time >= :time:", array('time' => $time))
                    ->andWhere("mode = :mode:", array('mode' => $model->mode))
                    ->andWhere("addr = :addr:", array('addr' => $model->addr));

                // 
                // Append source if set:
                // 
                if (isset($model->source)) {
                        $query->andWhere('source = :source:', array(
                                'source' => $model->source
                        ));
                }

                // 
                // Search for records being anchestor of this milestone.
                // 
                switch ($name) {
                        case self::MINUTE:
                                // 
                                // The minute milestone has no anchestor type.
                                // 
                                break;
                        case self::HOUR:
                                if ($this->_minute) {
                                        $query->andWhere('milestone = :type:', array(
                                                'type' => self::MINUTE
                                        ));
                                }
                                break;
                        case self::DAY:
                                if ($this->_hour) {
                                        $query->andWhere('milestone = :type:', array(
                                                'type' => self::HOUR
                                        ));
                                }
                                break;
                        case self::WEEK:
                                if ($this->_day) {
                                        $query->andWhere('milestone = :type:', array(
                                                'type' => self::DAY
                                        ));
                                }
                                break;
                        case self::MONTH:
                                if ($this->_week) {
                                        $query->andWhere('milestone = :type:', array(
                                                'type' => self::WEEK
                                        ));
                                }
                                break;
                        case self::YEAR:
                                if ($this->_month) {
                                        $query->andWhere('milestone = :type:', array(
                                                'type' => self::MONTH
                                        ));
                                }
                                break;
                }

                return $query->execute();
        }

        /**
         * Set milestone value.
         * @param string $name The milestone type (e.g. hour).
         */
        private function set($name)
        {
                switch ($name) {
                        case self::MINUTE:
                                $this->_minute = self::get($name, self::MINUTE);
                                break;
                        case self::HOUR:
                                $this->_hour = self::get($name, self::HOUR);
                                break;
                        case self::DAY:
                                $this->_day = self::get($name, self::DAY);
                                break;
                        case self::WEEK:
                                $this->_week = self::get($name, self::WEEK);
                                break;
                        case self::MONTH:
                                $this->_month = self::get($name, self::MONTH);
                                break;
                        case self::YEAR:
                                $this->_year = self::get($name, self::YEAR);
                                break;
                }
        }

        /**
         * Get last milestone.
         * 
         * Call this method to fetch last milestone. If milestone of requested 
         * type is missing, then it is calculated.
         * 
         * @param string $name The milestone type (e.g. hour).
         * @param string $type The counter type (e.g. disk).
         * @return int
         */
        private static function get($name, $type)
        {
                if (($result = Performance::findFirst(array(
                            'columns'    => 'time',
                            'conditions' => 'mode = :mode: AND milestone = :name:',
                            'bind'       => array(
                                    'mode' => $type,
                                    'name' => $name
                            ),
                            'limit'      => 1,
                            'order'      => 'time DESC',
                    )))) {
                        return strtotime($result['time']);
                } else {
                        return self::next($name);
                }
        }

        /**
         * Get next timestamp.
         * 
         * @param string $name The milestone type.
         * @return int
         */
        private static function next($name)
        {
                switch ($name) {
                        case self::MINUTE:
                                return time() + 60;
                        case self::HOUR:
                                return time() + 3600;
                        case self::DAY:
                                return time() + 3600 * 24;
                        case self::WEEK:
                                return time() + 3600 * 24 * 7;
                        case self::MONTH:
                                return time() + 3600 * 24 * 30;
                        case self::YEAR:
                                return time() + 3600 * 24 * 365;
                }
        }

        /**
         * Get previous timestamp.
         * 
         * @param string $name The milestone type.
         * @return int
         */
        private static function prev($name)
        {
                switch ($name) {
                        case self::MINUTE:
                                return time() - 60;
                        case self::HOUR:
                                return time() - 3600;
                        case self::DAY:
                                return time() - 3600 * 24;
                        case self::WEEK:
                                return time() - 3600 * 24 * 7;
                        case self::MONTH:
                                return time() - 3600 * 24 * 30;
                        case self::YEAR:
                                return time() - 3600 * 24 * 365;
                }
        }

}
