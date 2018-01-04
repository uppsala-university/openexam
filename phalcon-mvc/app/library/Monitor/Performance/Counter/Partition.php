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
         * The I/O counter.
         */
        const IO = 'io';

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
                return $this->tr->_("Partition Statistics (%part%)", array(
                            'part' => $this->_performance->getSource()
                ));
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
                        'label' => $this->getTitle(),
                        'descr' => $this->getDescription(),
                        'io'    => array(
                                'label'  => $this->tr->_("I/O"),
                                'descr'  => $this->tr->_("Total request and sector I/O for this partition."),
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
                return $type == self::IO;
        }

        /**
         * Check if counter uses source field.
         * 
         * The partition performance counter supports multiple sources. The 
         * returned list is a variable length list of all partition names.
         * 
         * @return array
         */
        public function getSources()
        {
                return CounterQuery::getSources($this->getType());
        }

        /**
         * Check if counter uses source field.
         * 
         * The partition performance counter supports multiple sources and will 
         * always return true.
         * 
         * @return boolean
         */
        public function hasSource()
        {
                return true;
        }

}
