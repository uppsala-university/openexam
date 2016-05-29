<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PerformanceCollector.php
// Created: 2016-05-23 23:08:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Process;
use OpenExam\Library\Monitor\Performance\Collector;

/**
 * Abstract base class for performance collectors.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class PerformanceCollector implements Collector
{

        /**
         * The process.
         * @var Process 
         */
        protected $_process;
        /**
         * The address for this server.
         * @var string
         */
        protected $_addr;
        /**
         * The hostname for this server.
         * @var string 
         */
        protected $_host;

        /**
         * Constructor.
         * @param Process $process The process.
         */
        protected function __construct($process)
        {
                $this->_addr = gethostbyname(gethostname());
                $this->_host = gethostbyaddr($this->_addr);

                $this->_process = $process;
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

        /**
         * Save performance data.
         * @return boolean
         */
        abstract protected function save();
}
