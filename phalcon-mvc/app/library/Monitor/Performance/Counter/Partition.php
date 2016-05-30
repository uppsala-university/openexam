<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Partition.php
// Created: 2016-05-24 02:40:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Partition performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Partition extends CounterBase implements Counter
{

        /**
         * The counter name.
         */
        const TYPE = 'part';
        /**
         * Number of reads.
         */
        const READS = 'reads';
        /**
         * Number of sector reads.
         */
        const RDSECT = 'rdsect';
        /**
         * Number of writes.
         */
        const WRITES = 'writes';
        /**
         * Number of write requests.
         */
        const WRREQ = 'wrreq';

        /**
         * Constructor.
         * @param Performance $performance The performance object.
         */
        public function __construct($performance)
        {
                parent::__construct(self::TYPE, $performance);
        }

        /**
         * Get counter name (short name).
         * @return string
         */
        public function getName()
        {
                return $this->tr->_("Partition");
        }

        /**
         * Get counter title (longer name).
         * @return string
         */
        public function getTitle()
        {
                return $this->tr->_("Partition Statistics (%s)");
        }

        /**
         * Get counter description.
         * @return string
         */
        public function getDescription()
        {
                return $this->tr->_("Read and write statistics for this partition.");
        }

        /**
         * Get translated performance counter keys.
         * @return array
         */
        public function getKeys()
        {
                return array(
                        'label'  => $this->getTitle(),
                        'descr'  => $this->getDescription(),
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
                );
        }

        /**
         * Check if sub counter exists.
         * 
         * This method will always return false as the partition itself is
         * an sub counter.
         * 
         * @param string $type The sub counter name.
         * @return boolean
         */
        public function hasCounter($type)
        {
                return false;   // No sub counters.
        }

}
