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
// File:    Test.php
// Created: 2016-06-04 15:53:57
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Models\Performance;

/**
 * Test performance collector.
 * 
 * This collector is solely for testing the performance counter framework 
 * during development and should not be enabled in production mode.
 * 
 * Its purpose is to generate a sequence of performance data with predictable 
 * values (constant, linear and exponential growing). The data can then be
 * used to test the performance counter interface.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Test extends CollectorBase
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 5;

        /**
         * Exit flag.
         * @var boolean 
         */
        private $_done = false;
        /**
         * The sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * Current iteration.
         * @var int 
         */
        private $_iteration = 0;

        /**
         * Constructor.
         * @param int $rate The sample rate.
         */
        public function __construct($rate = self::SAMPLE_RATE)
        {
                parent::__construct();
                $this->_rate = $rate;
        }

        /**
         * Save performance data.
         */
        protected function save()
        {
                $data = array(
                        'const'  => array(
                                'value' => 10
                        ),
                        'linear' => array(
                                'value' => $this->_iteration
                        ),
                        'exp'    => array(
                                'value' => pow(2, $this->_iteration)
                        )
                );

                $model = new Performance();
                $model->data = $data;
                $model->mode = Performance::MODE_TEST;
                $model->host = $this->_host;
                $model->addr = $this->_addr;

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }

                $model = new Performance();
                $model->data = $data;
                $model->mode = Performance::MODE_TEST;
                $model->host = "host1.example.com";
                $model->addr = "192.168.1.2";

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }

                $model = new Performance();
                $model->data = $data;
                $model->mode = Performance::MODE_TEST;
                $model->host = "host2.example.com";
                $model->addr = "192.168.1.3";

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }


                $this->_iteration++;
                return true;
        }

        /**
         * Start collector.
         */
        public function start()
        {
                while (!$this->_done) {
                        $this->save();
                        sleep($this->_rate);
                }
        }

        /**
         * Stop collector.
         */
        public function stop()
        {
                $this->_done = true;
        }

}
