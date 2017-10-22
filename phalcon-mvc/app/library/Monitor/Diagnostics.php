<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Diagnostics.php
// Created: 2016-06-02 02:28:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor;

use OpenExam\Library\Monitor\Diagnostics\Authenticator as AuthServiceCheck;
use OpenExam\Library\Monitor\Diagnostics\Catalog as CatalogServiceCheck;
use OpenExam\Library\Monitor\Diagnostics\Database as DatabaseServiceCheck;
use OpenExam\Library\Monitor\Diagnostics\ServiceCheck;
use OpenExam\Library\Monitor\Diagnostics\WebServer as WebServerCheck;

/**
 * System diagnostics.
 * 
 * Registry of service checks (diagnostics). This class also provides for
 * handling all service checks uniform:
 * 
 * <code>
 * $diag = new Diagnostics();
 * 
 * if ($diag->isOnline() && $diag->isWorking()) {
 *      $response->sendResult(array(
 *              "ok" => true
 *      ));
 * } else {
 *      $response->sendResult(array(
 *              "status" => $diag->getResult()
 *      ));
 * }
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Diagnostics implements ServiceCheck
{

        /**
         * The available checks.
         * @var array 
         */
        private $_checks = array();

        /**
         * Constructor.
         */
        public function __construct()
        {
                $this->register('auth', AuthServiceCheck::class);
                $this->register('catalog', CatalogServiceCheck::class);
                $this->register('database', DatabaseServiceCheck::class);
                $this->register('web', WebServerCheck::class);
        }

        /**
         * Test if service check is registered.
         * @param string $name The check name (e.g. 'auth')
         * @return boolean
         */
        public function hasServiceCheck($name)
        {
                return array_key_exists($name, $this->_checks);
        }

        /**
         * Get service check.
         * @param string $name The service check name (e.g. 'auth').
         * @return ServiceCheck
         */
        public function getServiceCheck($name)
        {
                if (!isset($this->_checks[$name])) {
                        return false;
                } else {
                        return $this->getCheckInstance($name);
                }
        }

        /**
         * Get all service checks.
         * @return ServiceCheck[]
         */
        public function getServiceChecks()
        {
                return $this->_checks;
        }

        /**
         * Register an diagnostics test.
         * @param string $name The diagnostics test name.
         * @param string $type The type name (class).
         */
        public function register($name, $type)
        {
                $this->_checks[$name] = $type;
        }

        /**
         * Get result from all service checks.
         * @return array
         */
        public function getResult()
        {
                $result = array();

                foreach (array_keys($this->_checks) as $name) {
                        $result[$name] = $this->getCheckInstance($name)->getResult();
                }

                return $result;
        }

        /**
         * Check online status for all service checks.
         * @return array
         */
        public function isOnline()
        {
                $result = array();

                foreach (array_keys($this->_checks) as $name) {
                        $result[$name] = $this->getCheckInstance($name)->isOnline();
                }

                return $result;
        }

        /**
         * Check working status for all service checks.
         * @return array
         */
        public function isWorking()
        {
                $result = array();

                foreach (array_keys($this->_checks) as $name) {
                        $result[$name] = $this->getCheckInstance($name)->isWorking();
                }

                return $result;
        }

        /**
         * Check failure status for all service checks.
         * 
         * This method returns false if at least of the services has failed.
         * One of the isOnline() or isWorking() has to be called in advance
         * to update the service check state.
         * 
         * @return boolean
         */
        public function hasFailed()
        {
                foreach (array_keys($this->_checks) as $name) {
                        if ($this->getCheckInstance($name)->hasFailed()) {
                                return true;
                        }
                }

                return false;
        }

        /**
         * On demand instantiate the service check.
         * 
         * @param string $name The service check name.
         * @return ServiceCheck
         */
        private function getCheckInstance($name)
        {
                if (!is_object($this->_checks[$name])) {
                        $this->_checks[$name] = new $this->_checks[$name]();
                }

                return $this->_checks[$name];
        }

}
