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
// File:    OnlineStatus.php
// Created: 2016-06-01 23:48:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

use OpenExam\Library\Console\Command;
use OpenExam\Library\Monitor\Exception;

/**
 * Check online status of hostname.
 * 
 * The test is done using ping and checking exit status to detect if ICMP
 * succeeded or not. It also goes a bit further by resolving hostname to 
 * multiple servers.
 * 
 * <code>
 * $online = new OnlineStatus("www.example.com");
 * $online->checkStatus();
 * 
 * if($online->hasFailed()) {
 *      foreach($online->getResult() as $addr => $status) {
 *              if($status == false) {
 *                      fprintf(stderr, "Server %s is offline\n", $addr);
 *              }
 *      }
 * }
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class OnlineStatus
{

        /**
         * True if last check has failed.
         * @var boolean 
         */
        private $_failed = false;
        /**
         * Online status result.
         * @var array 
         */
        private $_status = array();
        /**
         * The hostname to check.
         * @var string 
         */
        private $_hostname;

        /**
         * Constructor.
         * @param string $hostname The hostname to check.
         */
        public function __construct($hostname)
        {
                $this->_hostname = $hostname;
        }

        /**
         * Get result from online status check.
         * @return array
         */
        public function getResult()
        {
                return $this->_status;
        }

        /**
         * Get checked hostname.
         * @return string
         */
        public function getHostname()
        {
                return $this->_hostname;
        }

        /**
         * Set hostname for status lookup.
         * 
         * Calling this method will clear previous collected online status.
         * You need to call checkStatus() once again.
         * 
         * @param string $hostname The hostname to check
         */
        public function setHostname($hostname)
        {
                $this->_status = array();
                $this->_hostname = $hostname;
        }

        /**
         * Return true if at least one server failed to reply.
         * @return boolean
         */
        public function hasFailed()
        {
                return $this->_failed;
        }

        /**
         * Get array of resolved IP addresses.
         * @return array 
         */
        public function getAddresses()
        {
                return array_keys($this->_status);
        }

        /**
         * Perform status check.
         * 
         * Returns true if all servers resolved by current hostname is alive.
         * @return boolean
         */
        public function checkStatus()
        {
                $this->_status = array();
                $this->_failed = false;

                if (!($addresses = gethostbynamel($this->_hostname))) {
                        throw new Exception("Failed resolve hostname $this->_hostname");
                }

                foreach ($addresses as $addr) {
                        if (self::isOnline($addr)) {
                                $this->_status[$addr] = true;   // Online
                        } else {
                                $this->_status[$addr] = false;  // Offline
                                $this->_failed = true;
                        }
                }

                return $this->_failed != true;
        }

        /**
         * Check online status for this server.
         * @param string $addr The server address.
         * @return boolean
         */
        public static function isOnline($addr)
        {
                $command = new Command("ping -c 1 -W 2 $addr");
                $command->execute();
                return $command->getStatus() == 0;
        }

        /**
         * Reverse lookup of hostname from IP-address.
         * 
         * Returns the host name on success, the IP-address on failure. If input is
         * malformed, then false is returned.
         * 
         * @param string $addr The IP-address.
         * @return string
         */
        public static function getServerName($addr)
        {
                return gethostbyaddr($addr);
        }

}
