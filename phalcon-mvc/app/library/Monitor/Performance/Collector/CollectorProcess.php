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
// File:    CollectorProcess.php
// Created: 2016-05-29 23:54:33
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Process;

/**
 * Process runner.
 * 
 * Abstract base class for performance collectors based on commands that
 * periodical output data for capture.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class CollectorProcess extends CollectorBase
{

        /**
         * The process.
         * @var Process 
         */
        protected $_process;

        /**
         * Constructor.
         * @param Process $process The process.
         */
        protected function __construct($process)
        {
                parent::__construct();
                $this->_process = $process;
        }

        /**
         * Get process object.
         * @return Process
         */
        public function getProcess()
        {
                return $this->_process;
        }

        /**
         * Start performance collector.
         */
        public function start()
        {
                $this->_process->open();
                $this->_process->setBlocking(false);

                while (true) {
                        switch ($this->_process->wait()) {
                                case Process::WAIT_READY:
                                        $this->save();
                                        break;
                                case Process::WAIT_FAILED:
                                        break;
                                case Process::WAIT_TIMEOUT:
                                        break;
                        }
                }
        }

        /**
         * Stop performance collector.
         */
        public function stop()
        {
                if (!$this->_process->close()) {
                        if (!$this->_process->terminate()) {
                                $this->_process->terminate(SIGKILL);
                        }
                }
        }

}
