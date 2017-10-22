<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Network.php
// Created: 2016-05-31 21:30:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Network performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Network extends CounterBase implements Counter
{

        /**
         * The counter name.
         */
        const TYPE = 'net';
        /**
         * The receive counter.
         */
        const RECEIVED = 'receive';
        /**
         * The transmit counter.
         */
        const TRANSMIT = 'transmit';

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
                return $this->tr->_("Network");
        }

        /**
         * Get counter title (longer name).
         * @return string
         */
        public function getTitle()
        {
                return $this->tr->_("Network Interface (%nic%)", array(
                            'nic' => $this->_performance->getSource()
                ));
        }

        /**
         * Get counter description.
         * @return string
         */
        public function getDescription()
        {
                return $this->tr->_("Performance counter for network interface");
        }

        /**
         * Get translated performance counter keys.
         * @return array
         */
        public function getKeys()
        {
                return array(
                        'label'    => $this->getTitle(),
                        'descr'    => $this->getDescription(),
                        'receive'  => array(
                                'label'      => $this->tr->_("Receive"),
                                'descr'      => $this->tr->_("Information about incoming traffic on network interface"),
                                'bytes'      => array(
                                        'label' => $this->tr->_("Bytes"),
                                        'descr' => $this->tr->_("The total number of bytes of data received by the interface")
                                ),
                                'packets'    => array(
                                        'label' => $this->tr->_("Packets"),
                                        'descr' => $this->tr->_("The total number of packets of data received by the interface")
                                ),
                                'errs'       => array(
                                        'label' => $this->tr->_("Errors"),
                                        'descr' => $this->tr->_("The total number of receive errors detected by the device driver")
                                ),
                                'drop'       => array(
                                        'label' => $this->tr->_("Dropped"),
                                        'descr' => $this->tr->_("The total number of packets dropped by the device driver")
                                ),
                                'fifo'       => array(
                                        'label' => $this->tr->_("FIFO"),
                                        'descr' => $this->tr->_("The number of FIFO buffer errors (underruns)")
                                ),
                                'frame'      => array(
                                        'label' => $this->tr->_("Frame"),
                                        'descr' => $this->tr->_("The number of packet framing errors (e.g. too-long, ring-buffer overflow, CRC or alignment)")
                                ),
                                'compressed' => array(
                                        'label' => $this->tr->_("Compressed"),
                                        'descr' => $this->tr->_("The number of compressed packets received by the device driver")
                                ),
                                'multicast'  => array(
                                        'label' => $this->tr->_("Multicast"),
                                        'descr' => $this->tr->_("The number of multicast frames transmitted or received by the device driver")
                                ),
                        ),
                        'transmit' => array(
                                'label'      => $this->tr->_("Transmit"),
                                'descr'      => $this->tr->_("Information about outgoing traffic on network interface"),
                                'bytes'      => array(
                                        'label' => $this->tr->_("Bytes"),
                                        'descr' => $this->tr->_("The total number of bytes of data transmitted by the interface")
                                ),
                                'packets'    => array(
                                        'label' => $this->tr->_("Packets"),
                                        'descr' => $this->tr->_("The total number of packets of data transmitted by the interface")
                                ),
                                'errs'       => array(
                                        'label' => $this->tr->_("Errors"),
                                        'descr' => $this->tr->_("The total number of transmit errors detected by the device driver")
                                ),
                                'drop'       => array(
                                        'label' => $this->tr->_("Dropped"),
                                        'descr' => $this->tr->_("The total number of packets dropped by the device driver")
                                ),
                                'fifo'       => array(
                                        'label' => $this->tr->_("FIFO"),
                                        'descr' => $this->tr->_("The number of FIFO buffer errors (overruns)")
                                ),
                                'colls'      => array(
                                        'label' => $this->tr->_("Collisions"),
                                        'descr' => $this->tr->_("The number of collisions detected on the interface")
                                ),
                                'carrier'    => array(
                                        'label' => $this->tr->_("Carrier"),
                                        'descr' => $this->tr->_("The number of carrier losses detected by the device driver (incl. aborted, window size and heartbeat errors)")
                                ),
                                'compressed' => array(
                                        'label' => $this->tr->_("Compressed"),
                                        'descr' => $this->tr->_("The number of compressed packets transmitted by the device driver")
                                )
                        ),
                );
        }

        /**
         * Check if sub counter type exist.
         * @param string $type The sub counter type.
         * @return boolean
         */
        public function hasCounter($type)
        {
                return $type == self::RECEIVED || $type == self::TRANSMIT;
        }

        /**
         * Check if counter uses source field.
         * 
         * The network performance counter supports multiple sources. The 
         * returned list is a variable length list of all interfaces that has 
         * performance data.
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
         * The network performance  counter supports multiple sources and will 
         * always return true.
         * 
         * @return boolean
         */
        public function hasSource()
        {
                return true;
        }

}
