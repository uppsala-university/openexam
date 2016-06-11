<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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

                        foreach ($this->_triggers as $trigger) {
                                $trigger->process($model);
                        }
                }

                return true;
        }

}
