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
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 5;
        /**
         * Default partition to use.
         */
        const DEFAULT_PART = '/dev/sda1';
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
        public function __construct($rate = 5, $part = self::DEFAULT_PART)
        {
                if (!$rate) {
                        $rate = self::SAMPLE_RATE;
                }
                if (!$part) {
                        $part = self::DEFAULT_PART;
                }

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
                while (($line = $this->_process->read()) !== false) {

                        $vals = preg_split("/\s+/", trim($line));

                        if (count($vals) != 4) {
                                continue;
                        }

                        $data = array(
                                'io' => array(
                                        'reads'  => $vals[0],
                                        'rdsect' => $vals[1],
                                        'writes' => $vals[2],
                                        'wrreq'  => $vals[3]
                                )
                        );

                        $model = new Performance();
                        $model->data = $data;
                        $model->mode = Performance::MODE_PARTITION;
                        $model->host = $this->_host;
                        $model->addr = $this->_addr;
                        $model->source = $this->_part;

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
