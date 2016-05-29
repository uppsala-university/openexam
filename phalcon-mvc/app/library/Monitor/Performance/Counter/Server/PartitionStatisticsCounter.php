<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PartitionStatisticsCounter.php
// Created: 2016-05-24 02:40:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter\Server;

use OpenExam\Library\Monitor\Performance\Counter;
use OpenExam\Library\Monitor\Performance\Counter\PerformanceCounter;

/**
 * Partition statistics performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class PartitionStatisticsCounter extends PerformanceCounter implements Counter
{

        /**
         * Constructor.
         * @param array $data The performance data.
         */
        public function __construct($data = null)
        {
                parent::__construct($data, array(
                        'label'  => $this->tr->_("Partition Statistics (%s)"),
                        'descr'  => $this->tr->_("Read and write statistics for this partition."),
                        'reads'  => array(
                                'label' => $this->tr->_("Reads"),
                                'descr' => $this->tr->_("Total number of reads issued to this partition."),
                        ),
                        'rdsect' => array(
                                'label' => $this->tr->_("Sectors (Read)"),
                                'descr' => $this->tr->_("Total read sectors for partition."),
                        ),
                        'writes' => array(
                                'label' => $this->tr->_("Writes"),
                                'descr' => $this->tr->_("Total number of writes issued to this partition."),
                        ),
                        'wrreq'  => array(
                                'label' => $this->tr->_("Requests (Write)"),
                                'descr' => $this->tr->_("Total number of write requests made for partition."),
                        )
                ));
        }

}
