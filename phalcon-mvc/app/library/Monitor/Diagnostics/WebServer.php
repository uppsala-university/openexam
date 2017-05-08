<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    WebServer.php
// Created: 2017-05-05 15:41:30
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

use Phalcon\Mvc\User\Component;

/**
 * Server status check helper.
 * 
 * @property-read array $heades The response headers.
 * @property-read boolean $success The request was successful.
 */
class ServerStatus
{

        /**
         * The response headers.
         * @var array 
         */
        private $_headers;
        /**
         * The request was successful.
         * @var boolean 
         */
        private $_success;

        /**
         * Constructor.
         */
        public function __construct()
        {
                stream_context_set_default(
                    array(
                            'http' => array(
                                    'method' => 'HEAD'
                            )
                    )
                );
        }

        public function __get($name)
        {
                switch ($name) {
                        case 'headers':
                                return $this->_headers;
                        case 'success':
                                return $this->_success;
                }
        }

        /**
         * Fetch headers from URL.
         * 
         * Returns true if HTTP status code is less than 400.
         * 
         * @param string $url The endpoint URL.
         * @return boolean
         */
        public function fetch($url)
        {
                if (($this->_headers = get_headers($url)) !== false) {
                        $status = explode(" ", $this->_headers[0]);
                        $this->_success = $status[1] < 400;
                } else {
                        $this->_success = false;
                }

                return $this->_success;
        }

}

/**
 * Web server diagnostics.
 * 
 * Provides check of frontend, backend and load balancer. The latter two are
 * optional. The config can be defined in system config. If missing, then the
 * defaults are to check only the frontend detected from SERVER_NAME.
 * 
 * A full-blown config might look like this:
 * 
 * Array
 * (
 *   [frontend] => Array
 *       (
 *           [host] => openexam.bmc.uu.se       // Public server address.
 *           [port] => Array                    // Open server ports.
 *               (
 *                   [80]  => http
 *                   [443] => https
 *               )
 *       )
 *
 *  [backend] => Array
 *       (
 *           [host] => openexam-www.bmc.uu.se   // Round-robin DNB-record.
 *           [port] => Array                    // SSL terminated on frontend.
 *               (
 *                   [80] => http
 *               )
 *           [path] => /server-diag.php         // I.e. server load monitor
 *
 *       )
 *
 *   [balancer] => Array                        // The failover solution (LVS).
 *       (
 *           [host] => Array
 *               (
 *                   [0] => ws12.bmc.uu.se      // The backup IPVS.
 *                   [1] => ws13.bmc.uu.se      // The master IPVS.
 *               )
 *
 *       )
 * )
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class WebServer extends Component implements ServiceCheck
{

        /**
         * Web servers.
         * @var array 
         */
        private $_servers;
        /**
         * The result from check.
         * @var array 
         */
        private $_result;
        /**
         * True if test has failed.
         * @var boolean 
         */
        private $_failed = false;

        public function __construct($servers = null)
        {
                if (isset($servers)) {
                        $this->setServers($servers);
                } elseif (isset($this->config->diagnostics->web)) {
                        $this->setServers(
                            $this->config->diagnostics->web->toArray()
                        );
                } else {
                        $this->setServers(
                            array(
                                    'frontend' => array(
                                            'host' => filter_input(INPUT_SERVER, 'SERVER_NAME'),
                                            'port' => array(80 => 'http')
                                    ))
                        );
                }
        }

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
         * @throws Exception
         */
        public function isOnline()
        {
                $this->_failed = false;

                foreach ($this->_servers as $type => $endpoint) {
                        if (is_string($endpoint['host'])) {
                                $online = new OnlineStatus($endpoint['host']);

                                if (!$online->checkStatus()) {
                                        $this->_result[$type]['online'] = $online->getResult();
                                        $this->_failed = true;
                                } else {
                                        $this->_result[$type]['online'] = $online->getResult();
                                }
                        }
                        if (is_array($endpoint['host'])) {
                                $result = array();
                                $online = new OnlineStatus(null);

                                foreach ($endpoint['host'] as $hostname) {
                                        $online->setHostname($hostname);
                                        if (!$online->checkStatus()) {
                                                $result = array_merge($result, $online->getResult());
                                                $this->_failed = true;
                                        } else {
                                                $result = array_merge($result, $online->getResult());
                                        }
                                }

                                $this->_result[$type]['online'] = $result;
                        }
                        if (isset($endpoint['port'])) {
                                $this->_result[$type]['status'] = $endpoint['port'];
                        }
                        if (isset($endpoint['path'])) {
                                $this->_result[$type]['path'] = $endpoint['path'];
                        } else {
                                $this->_result[$type]['path'] = '/';
                        }
                }
        }

        /**
         * Check if service is working.
         * @return boolean
         */
        public function isWorking()
        {
                $this->_failed = false;

                foreach ($this->_result as $type => $detect) {
                        $this->_result[$type]['working'] = true;
                        foreach ($detect['online'] as $addr => $online) {
                                if (!$online) {
                                        $this->_result[$type]['working'] = false;
                                        $this->_failed = true;
                                } elseif (isset($detect['status'])) {
                                        $status = new ServerStatus();
                                        $server = OnlineStatus::getServerName($addr);

                                        foreach ($detect['status'] as $port => $proto) {
                                                if ($status->fetch(sprintf("%s://%s:%d%s", $proto, $server, $port, $detect['path'])) == false) {
                                                        $this->_result[$type]['working'] = false;
                                                        $this->_failed = true;
                                                }
                                        }
                                }
                        }
                }
        }

        /**
         * True if last check has failed.
         * @boolean
         */
        public function hasFailed()
        {
                return $this->_failed;
        }

        /**
         * Get servers.
         * @return array
         */
        public function getServers()
        {
                return $this->_servers;
        }

        /**
         * Set servers.
         * @param array $servers The server addresses or names.
         * @throws Exception
         */
        public function setServers($servers)
        {
                if (!is_array($servers)) {
                        throw new Exception("Expected array containing at least frontend server");
                } else {
                        $this->_servers = $servers;
                }

                error_log(print_r($this->_servers, true));
        }

}
