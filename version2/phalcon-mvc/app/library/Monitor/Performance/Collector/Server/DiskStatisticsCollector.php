<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DiskStatisticsCollector.php
// Created: 2016-05-23 23:06:52
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector\Server;

use OpenExam\Library\Console\Process;
use OpenExam\Library\Monitor\Performance\Collector\PerformanceCollector;
use OpenExam\Models\Performance as PerformanceModel;

/**
 * Disk statistics collector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DiskStatisticsCollector extends PerformanceCollector
{

        /**
         * The command to execute.
         */
        const COMMAND = "vmstat -d -n %d | grep --line-buffered %s";

        /**
         * Sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * Source disk.
         * @var string 
         */
        private $_disk;

        /**
         * Constructor.
         * @param string $disk The disk name (e.g sda).
         */
        public function __construct($disk = "sda", $rate = 10)
        {
                $this->_rate = $rate;
                $this->_disk = $disk;
                
                $command = sprintf(self::COMMAND, $rate, $disk);
                parent::__construct(new Process($command));
        }

        /**
         * Save performance data.
         * @return boolean
         */
        protected function save()
        {
                $line = $this->_process->read();
                $vals = preg_split("/\s+/", trim($line));

                if (count($vals) != 11) {
                        return false;
                }
                if (!is_numeric($vals[1])) {
                        return false;
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

                $model = new PerformanceModel();
                $model->data = $data;
                $model->mode = PerformanceModel::MODE_DISK;
                $model->host = $this->_host;
                $model->addr = $this->_addr;
                $model->source = $this->_disk;

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }

                return true;
        }

}
