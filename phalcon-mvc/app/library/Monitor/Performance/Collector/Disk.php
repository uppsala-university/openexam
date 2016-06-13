<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Disk.php
// Created: 2016-05-23 23:06:52
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Process;
use OpenExam\Models\Performance;

/**
 * Disk performance collector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Disk extends CollectorProcess
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 30;
        /**
         * Default option for disk name.
         */
        const DEFAULT_DISK = null;
        /**
         * The command to execute.
         */
        const COMMAND = "vmstat -d -n %d";

        /**
         * Sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * Source disk.
         * @var string|array
         */
        private $_disk;

        /**
         * Constructor.
         * @param int $rate The sample rate.
         * @param string|array $disk The disk name (e.g sda).
         */
        public function __construct($rate = 30, $disk = null)
        {
                if (isset($disk)) {
                        if (is_string($disk) && strstr($disk, ':')) {
                                $disk = explode(':', $disk);
                        }
                }

                $this->_rate = $rate;
                $this->_disk = $disk;

                $command = sprintf(self::COMMAND, $rate);
                parent::__construct(new Process($command));
        }

        /**
         * Save performance data.
         * @return boolean
         */
        protected function save()
        {
                while (($line = $this->_process->read()) !== false) {

                        $vals = preg_split("/\s+/", trim($line));

                        if (count($vals) != 11) {
                                continue;
                        }
                        if (!is_numeric($vals[1])) {
                                continue;
                        }
                        if (isset($this->_disk)) {
                                if (is_string($this->_disk) && $vals[0] != $this->_disk) {
                                        continue;
                                }
                                if (is_array($this->_disk) && !in_array($vals[0], $this->_disk)) {
                                        continue;
                                }
                        }

                        $data = array(
                                'read'  => array(
                                        'total'   => $vals[1],
                                        'merged'  => $vals[2],
                                        'sectors' => $vals[3],
                                        'ms'      => $vals[4]
                                ),
                                'write' => array(
                                        'total'   => $vals[5],
                                        'merged'  => $vals[6],
                                        'sectors' => $vals[7],
                                        'ms'      => $vals[8]
                                ),
                                'io'    => array(
                                        'current' => $vals[9],
                                        'seconds' => $vals[10]
                                )
                        );

                        $model = new Performance();
                        $model->data = $data;
                        $model->mode = Performance::MODE_DISK;
                        $model->host = $this->_host;
                        $model->addr = $this->_addr;
                        $model->source = $vals[0];

                        if (!$model->save()) {
                                foreach ($model->getMessages() as $message) {
                                        trigger_error($message, E_USER_ERROR);
                                }
                                return false;
                        }

                        foreach ($this->getTriggers($model->source) as $trigger) {
                                $trigger->process($model);
                        }
                }

                return true;
        }

}
