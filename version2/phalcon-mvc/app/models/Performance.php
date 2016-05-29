<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Question.php
// Created: 2016-05-23 09:09:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Validator\Inclusionin;

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
         * Virtual memory performance counter mode.
         */
        const MODE_VM = 'vm';
        /**
         * Disk performance counter mode.
         */
        const MODE_DISK = 'disk';
        /**
         * Partition performance counter mode.
         */
        const MODE_PART = 'part';
        /**
         * Slab data performance counter mode.
         */
        const MODE_SLAB = 'slab';
        /**
         * I/O performance counter mode.
         */
        const MODE_IO = 'io';
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

        protected function initialize()
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
        protected function validation()
        {
                if (isset($this->mode)) {
                        $this->validate(new Inclusionin(array(
                                'field'  => 'mode',
                                'domain' => array(
                                        self::MODE_DISK,
                                        self::MODE_IO,
                                        self::MODE_PART,
                                        self::MODE_SLAB,
                                        self::MODE_VM
                                )
                        )));
                }
                if (isset($this->milestone)) {
                        $this->validate(new Inclusionin(array(
                                'field'  => 'milestone',
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
                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

        protected function beforeSave()
        {
                $this->data = serialize($this->data);
        }

        protected function afterSave()
        {
                $this->data = unserialize($this->data);
        }

        public function afterFetch()
        {
                $this->data = unserialize($this->data);
                parent::afterFetch();
        }

}
