<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
