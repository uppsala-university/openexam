<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
