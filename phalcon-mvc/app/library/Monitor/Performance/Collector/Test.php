<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
