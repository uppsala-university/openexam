<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Partition.php
// Created: 2016-05-23 23:44:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Process;
use OpenExam\Models\Performance;

/**
 * Partition performance collector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Partition extends CollectorProcess
{

        /**
         * The command to execute.
         */
        const COMMAND = "vmstat -p %s -n %d";

        /**
         * Sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * Source partition.
         * @var string 
         */
        private $_part;

        /**
         * Constructor.
         * @param string $part The source partition.
         * @param int $rate The sample rate.
         */
        public function __construct($part = "/dev/sda1", $rate = 60)
        {
                $this->_rate = $rate;
                $this->_part = $part;

                $command = sprintf(self::COMMAND, $part, $rate);
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

                if (count($vals) != 4) {
                        return false;
                }

                $data = array(
                        'reads'  => $vals[0],
                        'rdsect' => $vals[1],
                        'writes' => $vals[2],
                        'wrreq'  => $vals[3]
                );

                $model = new Performance();
                $model->data = $data;
                $model->mode = Performance::MODE_PART;
                $model->host = $this->_host;
                $model->addr = $this->_addr;
                $model->source = $this->_part;

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }

                return true;
        }

}
