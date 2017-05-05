<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Catalog.php
// Created: 2016-05-31 02:28:11
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

use Phalcon\Mvc\User\Component;

/**
 * Diagnostics of catalog services.
 *
 * Perform catalog service diagnostics against selected domain. If domain
 * is missing, then all domains are used. The default user domain is set
 * by system config.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Catalog extends Component implements ServiceCheck
{

        /**
         * The check result.
         * @var array 
         */
        private $_result = array();
        /**
         * True if test has failed.
         * @var boolean 
         */
        private $_failed = false;
        /**
         * The domains to check against.
         * @var array 
         */
        private $_domains;

        /**
         * Constructor.
         * @param string|array $domains The domain(s) to check.
         */
        public function __construct($domains = null)
        {
                if (!isset($domains)) {
                        $this->_domains = $this->catalog->getDomains();
                } elseif (is_array($domains)) {
                        $this->_domains = $domains;
                } else {
                        $this->_domains = array($domains);
                }
        }

        /**
         * Set the domain list.
         * @param array $domains The domain list.
         */
        public function setDomains($domains)
        {
                $this->_domains = $domains;
        }

        /**
         * Get the list of domains.
         * @return array
         */
        public function getDomains()
        {
                return $this->_domains;
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
         */
        public function isOnline()
        {
                $this->_failed = false;

                foreach ($this->_domains as $domain) {
                        if (!isset($this->_result[$domain])) {
                                $this->_result[$domain] = array();
                        }

                        foreach ($this->catalog->getServices($domain) as $service) {
                                if (($connection = $service->getConnection()) == null) {
                                        continue;
                                }

                                $hostname = $connection->hostname();
                                if (strstr($hostname, '://')) {
                                        $hostname = parse_url($hostname, PHP_URL_HOST);
                                }

                                $online = new OnlineStatus($hostname);

                                if (!$online->checkStatus()) {
                                        $this->_result[$domain][$service->getServiceName()]['online'] = $online->getResult();
                                        $this->_failed = true;
                                } else {
                                        $this->_result[$domain][$service->getServiceName()]['online'] = $online->getResult();
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

                foreach ($this->_domains as $domain) {
                        if (!isset($this->_result[$domain])) {
                                $this->_result[$domain] = array();
                        }

                        foreach ($this->catalog->getServices($domain) as $service) {
                                if (($connection = $service->getConnection()) == null) {
                                        $this->_result[$domain][$service->getServiceName()]['working'] = true;
                                } elseif ($connection->connected()) {
                                        $this->_result[$domain][$service->getServiceName()]['working'] = true;
                                } elseif ($connection->open()) {
                                        $this->_result[$domain][$service->getServiceName()]['working'] = true;
                                        $connection->close();
                                } else {
                                        $this->_result[$domain][$service->getServiceName()]['working'] = false;
                                        $this->_failed = true;
                                }
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
