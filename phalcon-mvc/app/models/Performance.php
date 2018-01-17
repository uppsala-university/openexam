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
// File:    Question.php
// Created: 2016-05-23 09:09:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;

/**
 * The performance model.
 * 
 * Represents data collected from an performance counter (e.g. vmstat(8) or 
 * equivalent).
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Performance extends ModelBase
{

        /**
         * Server performance counter mode.
         */
        const MODE_SERVER = 'server';
        /**
         * Disk performance counter mode.
         */
        const MODE_DISK = 'disk';
        /**
         * Partition performance counter mode.
         */
        const MODE_PARTITION = 'part';
        /**
         * File system performance counter mode.
         */
        const MODE_FILESYS = 'fs';
        /**
         * Slab data performance counter mode.
         */
        const MODE_SLAB = 'slab';
        /**
         * I/O performance counter mode.
         */
        const MODE_IO = 'io';
        /**
         * Test performance counter mode (use only for debug/test).
         */
        const MODE_TEST = 'test';
        /**
         * System performance counter mode.
         */
        const MODE_SYSTEM = 'system';
        /**
         * Apache performance counter mode.
         */
        const MODE_APACHE = 'apache';
        /**
         * MySQL performance counter mode.
         */
        const MODE_MYSQL = 'mysql';
        /**
         * Network performance counter mode.
         */
        const MODE_NETWORK = 'net';
        /**
         * This counter is a minute milestone.
         */
        const MILESTONE_MINUTE = 'minute';
        /**
         * This counter is a hour milestone.
         */
        const MILESTONE_HOUR = 'hour';
        /**
         * This counter is a day milestone.
         */
        const MILESTONE_DAY = 'day';
        /**
         * This counter is a week milestone.
         */
        const MILESTONE_WEEK = 'week';
        /**
         * This counter is a month milestone.
         */
        const MILESTONE_MONTH = 'month';
        /**
         * This counter is an year milestone.
         */
        const MILESTONE_YEAR = 'year';

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The counter mode. One of MODE_XXX.
         * @var string
         */
        public $mode;
        /**
         * The associated counter source.
         * @var string 
         */
        public $source;
        /**
         * The log time.
         * @var string
         */
        public $time;
        /**
         * The hostname.
         * @var string 
         */
        public $host;
        /**
         * The IP-address.
         * @var string 
         */
        public $addr;
        /**
         * Milestone type. One of MILESTONE_XXX constants or null.
         * @var string
         */
        public $milestone;
        /**
         * The counter data.
         * @var array
         */
        public $data;

        public function initialize()
        {
                if ($this->getDI()->has('audit')) {
                        $audit = $this->getDI()->get('audit');
                        $model = $this->getResourceName();

                        if ($audit->hasConfig($model)) {
                                $audit->setDisabled($model);
                        }
                }

                parent::initialize();

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'time',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'performance';
        }

        /**
         * The table column map.
         * @return array 
         */
        public function columnMap()
        {
                return array(
                        'id'        => 'id',
                        'mode'      => 'mode',
                        'source'    => 'source',
                        'time'      => 'time',
                        'host'      => 'host',
                        'addr'      => 'addr',
                        'milestone' => 'milestone',
                        'data'      => 'data'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        public function validation()
        {
                $validator = new Validation();

                if (isset($this->mode)) {
                        $validator->add(
                            'mode', new InclusionIn(array(
                                'domain' => array(
                                        self::MODE_APACHE,
                                        self::MODE_DISK,
                                        self::MODE_FILESYS,
                                        self::MODE_IO,
                                        self::MODE_MYSQL,
                                        self::MODE_NETWORK,
                                        self::MODE_PARTITION,
                                        self::MODE_SERVER,
                                        self::MODE_SLAB,
                                        self::MODE_SYSTEM,
                                        self::MODE_TEST
                                )
                        )));
                }
                if (isset($this->milestone)) {
                        $validator->add(
                            'milestone', new InclusionIn(array(
                                'domain' => array(
                                        self::MILESTONE_MINUTE,
                                        self::MILESTONE_HOUR,
                                        self::MILESTONE_DAY,
                                        self::MILESTONE_WEEK,
                                        self::MILESTONE_MONTH,
                                        self::MILESTONE_YEAR
                                )
                        )));
                }

                return $this->validate($validator);
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
                parent::afterFetch();
        }

}
