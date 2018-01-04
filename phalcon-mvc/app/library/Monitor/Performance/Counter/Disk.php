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
// File:    Disk.php
// Created: 2016-05-24 02:35:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Disk performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Disk extends CounterBase implements Counter
{

        /**
         * The counter type.
         */
        const TYPE = 'disk';
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
                return $this->tr->_("Disk");
        }

        /**
         * Get counter title (longer name).
         * @return string
         */
        public function getTitle()
        {
                return $this->tr->_("Disk Performance (%disk%)", array(
                            'disk' => $this->_performance->getSource()
                ));
        }

        /**
         * Get counter description.
         * @return string
         */
        public function getDescription()
        {
                return $this->tr->_("Disk I/O (read/write) statistics.");
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
                );
        }

        /**
         * Check if sub counter type exist.
         * @param string $type The sub counter type.
         * @return boolean
         */
        public function hasCounter($type)
        {
                return $type == self::IO || $type == self::READ || $type == self::WRITE;
        }

        /**
         * Check if counter uses source field.
         * 
         * The disk counter supports multiple sources. The returned list is
         * a variable length list of all disks that has performance data.
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
         * The disk counter supports multiple sources and will always return
         * true.
         * 
         * @return boolean
         */
        public function hasSource()
        {
                return true;
        }

}
