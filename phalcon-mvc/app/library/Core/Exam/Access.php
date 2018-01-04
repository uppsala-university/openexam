<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Access.php
// Created: 2017-09-23 00:39:27
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Core\Exam;

use InvalidArgumentException;
use OpenExam\Models\Exam;
use UUP\Authentication\Authenticator\DomainAuthenticator;
use UUP\Authentication\Authenticator\HostnameAuthenticator;
use UUP\Authentication\Restrictor\AddressRestrictor;

/**
 * Check exam access.
 * 
 * This class can be used to check whether remote peer is allowed to access
 * the exam based on its access list. Typical used for checking that student 
 * computer are permitted.
 *
 * @author Anders Lövgren (QNET)
 */
class Access
{

        /**
         * The regexp delimiter.
         */
        const REGEXP_DELIM = '|';

        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;
        /**
         * The address accept list.
         * @var array 
         */
        private $_addr = array();
        /**
         * The domain name (DNS) patterns.
         * @var array 
         */
        private $_dnsp = array();
        /**
         * The hostname accept list.
         * @var array 
         */
        private $_host = array();

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;

                foreach ($this->_exam->access as $access) {
                        $this->addAccess(rtrim($access->addr, ';'));
                }
        }

        /**
         * Returns true if access list is missing.
         * @return boolean
         */
        public function isMissing()
        {
                return empty($this->_addr) && empty($this->_host) && empty($this->_dnsp);
        }

        /**
         * Check IP-address restriction.
         * 
         * Returns false if remote address is not matching any address 
         * entries or if the address list is empty.
         * 
         * @param string $addr The remote address.
         * @return boolean 
         */
        public function hasAcceptedAddress($addr)
        {
                if (!empty($this->_addr)) {
                        foreach ($this->_addr as $allowed) {
                                $restrictor = new AddressRestrictor($allowed);
                                if ($restrictor->match($addr)) {
                                        return true;
                                }
                        }
                }
        }

        /**
         * Check domain name (DNS) restriction.
         * 
         * Returns false if hostname is not matching any of the DNS domain 
         * name patterns or if pattern list is empty. This list contains
         * regexp patterns to match against hostname.
         * 
         * @param string $host The remote hostname (FQHN).
         * @return boolean 
         */
        public function hasAcceptedDomain($host)
        {
                if (!empty($this->_dnsp)) {
                        $restrictor = new DomainAuthenticator();
                        foreach ($this->_dnsp as $accept) {
                                $restrictor->setHostname($accept);
                                if ($restrictor->match($host)) {
                                        return true;
                                }
                        }
                }
        }

        /**
         * Check hostname restriction.
         * 
         * Returns false if hostname is not matching any hostname entry or 
         * if the hostname list is missing.
         * 
         * @param string $host The remote hostname (FQHN).
         * @return boolean 
         */
        public function hasAcceptedHostname($host)
        {
                if (!empty($this->_host)) {
                        $restrictor = new HostnameAuthenticator();
                        foreach ($this->_host as $accept) {
                                $restrictor->setHostname($accept);
                                if ($restrictor->match($host)) {
                                        return true;
                                }
                        }
                }
        }

        /**
         * Check if access is allowed.
         * @param string $addr The remote address.
         * @return boolean
         */
        public function isAllowed($addr)
        {
                if ($this->hasAcceptedAddress($addr)) {
                        return true;
                }

                if (!($hostname = gethostbyaddr($addr))) {
                        throw new InvalidArgumentException("Invalid IP address for gethostbyaddr()");
                }

                if ($this->hasAcceptedHostname($hostname)) {
                        return true;
                }
                if ($this->hasAcceptedDomain($hostname)) {
                        return true;
                }
        }

        /**
         * Add access definition.
         * 
         * @param string $addr The address, domain or hostname.
         */
        private function addAccess($addr)
        {
                if ($addr[0] == '|') {
                        $this->_dnsp[] = $addr;
                } elseif (ctype_digit(substr($addr, -1))) {
                        $this->_addr[] = $addr;
                } else {
                        $this->_host = array_merge(
                            $this->_host, explode(';', $addr)
                        );
                }
        }

}
