<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DiskStatisticsCounter.php
// Created: 2016-05-24 02:35:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter\Server;

use OpenExam\Library\Monitor\Performance\Counter;
use OpenExam\Library\Monitor\Performance\Counter\PerformanceCounter;

/**
 * Disk statistics performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DiskStatisticsCounter extends PerformanceCounter implements Counter
{

        /**
         * The read counter.
         */
        const READ = 'read';
        /**
         * The write counter.
         */
        const WRITE = 'write';
        /**
         * The I/O counter.
         */
        const IO = 'io';

        /**
         * Constructor.
         * @param array $data The performance data.
         */
        public function __construct($data = null)
        {
                parent::__construct($data, array(
                        'label' => $this->tr->_("Disk Performance Counters"),
                        'descr' => $this->tr->_("Disk I/O (read/write) statistics."),
                        'read'  => array(
                                'label'   => $this->tr->_("Reads"),
                                'descr'   => $this->tr->_("Statistics for read operation."),
                                'total'   => array(
                                        'label' => $this->tr->_("Total"),
                                        'descr' => $this->tr->_("Total reads completed successfully.")
                                ),
                                'merged'  => array(
                                        'label' => $this->tr->_("Merged"),
                                        'descr' => $this->tr->_("Grouped reads (resulting in one I/O).")
                                ),
                                'sectors' => array(
                                        'label' => $this->tr->_("Sectors"),
                                        'descr' => $this->tr->_("Sectors read successfully.")
                                ),
                                'ms'      => array(
                                        'label' => $this->tr->_("Summary"),
                                        'descr' => $this->tr->_("Milliseconds spent reading.")
                                )
                        ),
                        'write' => array(
                                'label'   => $this->tr->_("Writes"),
                                'descr'   => $this->tr->_("Statistics for write operation."),
                                'total'   => array(
                                        'label' => $this->tr->_("Total"),
                                        'descr' => $this->tr->_("Total writes completed successfully.")
                                ),
                                'merged'  => array(
                                        'label' => $this->tr->_("Merged"),
                                        'descr' => $this->tr->_("Grouped writes (resulting in one I/O).")
                                ),
                                'sectors' => array(
                                        'label' => $this->tr->_("Sectors"),
                                        'descr' => $this->tr->_("Sectors written successfully.")
                                ),
                                'ms'      => array(
                                        'label' => $this->tr->_("Summary"),
                                        'descr' => $this->tr->_("Milliseconds spent writing.")
                                )
                        ),
                        'io'    => array(
                                'label'   => $this->tr->_("Disk I/O"),
                                'descr'   => $this->tr->_("Statistics for current I/O operations"),
                                'current' => array(
                                        'label' => $this->tr->_("Current"),
                                        'descr' => $this->tr->_("I/O in progress.")
                                ),
                                'seconds' => array(
                                        'label' => $this->tr->_("Summary"),
                                        'descr' => $this->tr->_("Seconds spent for I/O.")
                                )
                        )
                ));
        }

        /**
         * Get disk read counter.
         * @return array
         */
        public function getReadCounter()
        {
                return parent::getCounter(self::READ);
        }

        /**
         * Get disk write counter.
         * @return array
         */
        public function getWriteCounter()
        {
                return parent::getCounter(self::WRITE);
        }

        /**
         * Get disk I/O counter.
         * @return array
         */
        public function getIOCounter()
        {
                return parent::getCounter(self::IO);
        }

}
