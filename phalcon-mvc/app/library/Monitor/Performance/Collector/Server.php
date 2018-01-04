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
// File:    Server.php
// Created: 2016-05-23 01:35:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Process;
use OpenExam\Models\Performance;

/**
 * Server performance collector. 
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Server extends CollectorProcess
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 10;
        /**
         * The command to execute.
         */
        const COMMAND = "vmstat -S M -n %d";

        /**
         * Constructor.
         * @param int $rate The sample rate.
         */
        public function __construct($rate = 5)
        {
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

                        if (count($vals) != 17) {
                                continue;
                        }
                        if (!is_numeric($vals[0])) {
                                continue;
                        }

                        $data = array(
                                'process' => array(
                                        'runnable' => $vals[0],
                                        'sleeping' => $vals[1]
                                ),
                                'memory'  => array(
                                        'swap'   => $vals[2],
                                        'free'   => $vals[3],
                                        'buffer' => $vals[4],
                                        'cached' => $vals[5]
                                ),
                                'swap'    => array(
                                        'in'  => $vals[6],
                                        'out' => $vals[7]
                                ),
                                'io'      => array(
                                        'in'  => $vals[8],
                                        'out' => $vals[9]
                                ),
                                'system'  => array(
                                        'interrupts' => $vals[10],
                                        'context'    => $vals[11]
                                ),
                                'cpu'     => array(
                                        'user'   => $vals[12],
                                        'system' => $vals[13],
                                        'idle'   => $vals[14],
                                        'wait'   => $vals[15],
                                        'stolen' => $vals[16]
                                )
                        );

                        $model = new Performance();
                        $model->data = $data;
                        $model->mode = Performance::MODE_SERVER;
                        $model->host = $this->_host;
                        $model->addr = $this->_addr;

                        if (!$model->save()) {
                                foreach ($model->getMessages() as $message) {
                                        trigger_error($message, E_USER_ERROR);
                                }
                                return false;
                        }

                        foreach ($this->getTriggers() as $trigger) {
                                $trigger->process($model);
                        }
                }

                return true;
        }

}
