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
// Created: 2016-05-24 02:23:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Server performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Server extends CounterBase implements Counter
{

        /**
         * The counter name.
         */
        const TYPE = 'server';
        /**
         * The process counter.
         */
        const PROCESS = 'process';
        /**
         * The memory counter.
         */
        const MEMORY = 'memory';
        /**
         * The swap counter.
         */
        const SWAP = 'swap';
        /**
         * The I/O counter.
         */
        const IO = 'io';
        /**
         * The system counter.
         */
        const SYSTEM = 'system';
        /**
         * The CPU counter.
         */
        const CPU = 'cpu';

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
                return $this->tr->_("Server");
        }

        /**
         * Get counter title (longer name).
         * @return string
         */
        public function getTitle()
        {
                return $this->tr->_("Virtual Memory Counters");
        }

        /**
         * Get counter description.
         * @return string
         */
        public function getDescription()
        {
                return $this->tr->_("Information about processes, memory, paging, block I/O and CPU activity.");
        }

        /**
         * Get translated performance counter keys.
         * @return array
         */
        public function getKeys()
        {
                return array(
                        'label'   => $this->getTitle(),
                        'descr'   => $this->getDescription(),
                        'cpu'     => array(
                                'label'  => $this->tr->_("Processor (CPU)"),
                                'descr'  => $this->tr->_("These are percentages of total CPU time."),
                                'user'   => array(
                                        'label' => $this->tr->_("User Mode"),
                                        'descr' => $this->tr->_("Time spent running non-kernel code."),
                                ),
                                'system' => array(
                                        'label' => $this->tr->_("Kernel Mode"),
                                        'descr' => $this->tr->_("Time spent running kernel code."),
                                ),
                                'idle'   => array(
                                        'label' => $this->tr->_("Idle"),
                                        'descr' => $this->tr->_("Time spent idle."),
                                ),
                                'wait'   => array(
                                        'label' => $this->tr->_("I/O Wait"),
                                        'descr' => $this->tr->_("Time spent waiting for IO."),
                                ),
                                'stolen' => array(
                                        'label' => $this->tr->_("Stolen"),
                                        'descr' => $this->tr->_("Time stolen from a virtual machine."),
                                )
                        ),
                        'memory'  => array(
                                'label'  => $this->tr->_("Memory"),
                                'descr'  => $this->tr->_("RAM and swap used, including disk cache and buffers."),
                                'swap'   => array(
                                        'label' => $this->tr->_("Swap"),
                                        'descr' => $this->tr->_("The amount of virtual memory used."),
                                ),
                                'free'   => array(
                                        'label' => $this->tr->_("Free Memory"),
                                        'descr' => $this->tr->_("The amount of idle (unused) memory."),
                                ),
                                'buffer' => array(
                                        'label' => $this->tr->_("Buffered"),
                                        'descr' => $this->tr->_("The amount of memory used as buffers."),
                                ),
                                'cached' => array(
                                        'label' => $this->tr->_("Cached"),
                                        'descr' => $this->tr->_("The amount of memory used as cache."),
                                )
                        ),
                        'swap'    => array(
                                'label' => $this->tr->_("Swap"),
                                'descr' => $this->tr->_("Usage of disk paging."),
                                'in'    => array(
                                        'label' => $this->tr->_("Pages In"),
                                        'descr' => $this->tr->_("Amount of memory swapped in from disk (/s)."),
                                ),
                                'out'   => array(
                                        'label' => $this->tr->_("Pages Out"),
                                        'descr' => $this->tr->_("Amount of memory swapped to disk (/s)."),
                                )
                        ),
                        'process' => array(
                                'label'    => $this->tr->_("Processes"),
                                'descr'    => $this->tr->_("Information about running processes."),
                                'runnable' => array(
                                        'label' => $this->tr->_("Runnable"),
                                        'descr' => $this->tr->_("The number of runnable processes (running or waiting for run time)."),
                                ),
                                'sleeping' => array(
                                        'label' => $this->tr->_("Sleeping"),
                                        'descr' => $this->tr->_("The number of processes in uninterruptible sleep."),
                                )
                        ),
                        'io'      => array(
                                'label' => $this->tr->_("Block I/O"),
                                'descr' => $this->tr->_("Read/write for block devices."),
                                'in'    => array(
                                        'label' => $this->tr->_("Block In"),
                                        'descr' => $this->tr->_("Blocks received from a block device (blocks/s)."),
                                ),
                                'out'   => array(
                                        'label' => $this->tr->_("Block Out"),
                                        'descr' => $this->tr->_("Blocks sent to a block device (blocks/s)."),
                                )
                        ),
                        'system'  => array(
                                'label'      => $this->tr->_("System"),
                                'descr'      => $this->tr->_("IRQ and context switches."),
                                'interrupts' => array(
                                        'label' => $this->tr->_("Interrupts"),
                                        'descr' => $this->tr->_("The number of interrupts per second, including the clock."),
                                ),
                                'context'    => array(
                                        'label' => $this->tr->_("Context Switches"),
                                        'descr' => $this->tr->_("The number of context switches per second."),
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
                return
                    $type == self::CPU ||
                    $type == self::IO ||
                    $type == self::MEMORY ||
                    $type == self::PROCESS ||
                    $type == self::SWAP ||
                    $type == self::SYSTEM;
        }

}
