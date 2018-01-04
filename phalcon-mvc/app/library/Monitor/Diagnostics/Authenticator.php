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
// File:    Authenticator.php
// Created: 2016-05-31 02:28:39
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

use OpenExam\Library\Security\Login\Base\RemoteLogin;
use Phalcon\Mvc\User\Component;

/**
 * Diagnostics of authentication.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Authenticator extends Component implements ServiceCheck
{

        /**
         * The check result.
         * @var array 
         */
        private $_result = array();
        /**
         * True if last check has failed.
         * @var boolean
         */
        private $_failed;

        /**
         * Get check result.
         * @return array
         */
        public function getResult()
        {
                return $this->_result;
        }

        /**
         * Check if service is online.
         * @return boolean
         */
        public function isOnline()
        {
                $this->_failed = false;

                foreach ($this->auth->getChains() as $service => $plugins) {
                        foreach ($plugins as $name => $plugins) {
                                if (!$this->auth->activate($name, $service)) {
                                        continue;
                                }
                                if (!($plugin = $this->auth->getAuthenticator())) {
                                        continue;
                                }
                                if (!($plugin instanceof RemoteLogin)) {
                                        continue;
                                }

                                $hostname = $plugin->hostname();
                                if (strstr($hostname, '://')) {
                                        $hostname = parse_url($hostname, PHP_URL_HOST);
                                }

                                if (!isset($this->_result[$service])) {
                                        $this->_result[$service] = array();
                                }

                                $online = new OnlineStatus($hostname);
                                if ($online->checkStatus()) {
                                        $this->_result[$service][$name]['online'] = $online->getResult();
                                } else {
                                        $this->_result[$service][$name]['online'] = $online->getResult();
                                        $this->_failed = true;
                                }
                        }
                }

                return $this->_failed != true;
        }

        /**
         * Check if service is working.
         * @return boolean
         */
        public function isWorking()
        {
                $this->_failed = false;

                // 
                // Checking if authentication is actually working would be too complex, 
                // we have to fake here ;-) 
                // 
                // We make the (possibly naive) assumption that if an authentication 
                // system is online, then it's also functional.
                //  
                foreach ($this->auth->getChains() as $service => $plugins) {
                        foreach ($plugins as $name => $plugins) {
                                if (!$this->auth->activate($name, $service)) {
                                        continue;
                                }
                                if (!($plugin = $this->auth->getAuthenticator())) {
                                        continue;
                                }
                                if (!($plugin instanceof RemoteLogin)) {
                                        continue;
                                }
                                if (!isset($this->_result[$service])) {
                                        $this->_result[$service] = array();
                                }

                                // 
                                // Deduce from servers offline status.
                                // 
                                $this->_result[$service][$name]['working'] = array_reduce(
                                    $this->_result[$service][$name]['online'], function($c, $i) {
                                        return $c && $i;
                                }, true);
                        }
                }

                return $this->_failed != true;
        }

        /**
         * True if last check has failed.
         * @boolean
         */
        public function hasFailed()
        {
                return $this->_failed;
        }

}
