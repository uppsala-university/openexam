<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DiagnosticsTask.php
// Created: 2016-05-22 23:18:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Monitor\Diagnostics\Authenticator as AuthServiceDiagnostics;
use OpenExam\Library\Monitor\Diagnostics\Catalog as CatalogServiceDiagnostics;
use OpenExam\Library\Monitor\Diagnostics\Database as DatabaseDiagnostics;
use OpenExam\Library\Monitor\Diagnostics\OnlineStatus;

/**
 * System diagnostics task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DiagnosticsTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'System diagnostics tool',
                        'action'   => '--diagnostics',
                        'usage'    => array(
                                '--check [--auth] [--catalog] [--database] [--online=fqhn] [--all]'
                        ),
                        'options'  => array(
                                '--check'       => 'Run diagnostics test (all by default).',
                                '--auth'        => 'Check authentication service(s).',
                                '--catalog'     => 'Check catalog service(s).',
                                '--database'    => 'Check database services.',
                                '--online=fqhn' => 'Check online status.',
                                '--all'         => 'Check all services.',
                                '--verbose'     => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Run all diagnostics',
                                        'command' => '--check --all'
                                ),
                                array(
                                        'descr'   => 'Check only database service',
                                        'command' => '--check --database'
                                ),
                                array(
                                        'descr'   => 'Check web server online status (round robin hostname)',
                                        'command' => '--check --online=openexam.bmc.uu.se'
                                )
                        )
                );
        }

        /**
         * System diagnostics check action.
         * @param array $params
         */
        public function checkAction($params = array())
        {
                $this->setOptions($params, 'check');

                if ($this->_options['auth']) {
                        $this->checkAuthentication();
                }
                if ($this->_options['catalog']) {
                        $this->checkCatalogService();
                }
                if ($this->_options['database']) {
                        $this->checkDatabase();
                }
                if ($this->_options['online']) {
                        $this->checkOnlineStatus($this->_options['online']);
                }
        }

        /**
         * Authentication check action.
         * @param array $params
         */
        public function authAction($params = array())
        {
                $this->setOptions($params, 'auth');
                $this->checkAuthentication();
        }

        /**
         * Catalog check action.
         * @param array $params
         */
        public function catalogAction($params = array())
        {
                $this->setOptions($params, 'catalog');
                $this->checkCatalogService();
        }

        /**
         * Database check action.
         * @param array $params
         */
        public function databaseAction($params = array())
        {
                $this->setOptions($params, 'daatbase');
                $this->checkDatabase();
        }

        /**
         * Online status check action.
         * @param array $params
         */
        public function onlineAction($params = array())
        {
                $this->setOptions($params, 'online');
                $this->checkOnlineStatus($this->_options['online']);
        }

        /**
         * Check status for the authenticator service and all chains.
         */
        private function checkAuthentication()
        {
                $authentication = new AuthServiceDiagnostics();

                if ($authentication->isOnline()) {
                        $this->flash->success("Authentication is online");
                } else {
                        $failed = true;
                        $this->flash->error("Authentication is offline");
                }
                if ($authentication->isWorking()) {
                        $this->flash->success("Authentication is working");
                } else {
                        $failed = true;
                        $this->flash->error("Authentication is not working");
                }

                if ($this->_options['verbose'] || isset($failed)) {
                        $this->flash->notice(print_r($authentication->getResult(), true));
                }
        }

        /**
         * Check the catalog service and its directory services.
         */
        private function checkCatalogService()
        {
                $catalog = new CatalogServiceDiagnostics;

                if ($catalog->isOnline()) {
                        $this->flash->success("Catalog is online");
                } else {
                        $failed = true;
                        $this->flash->error("Catalog is offline");
                }
                if ($catalog->isWorking()) {
                        $this->flash->success("Catalog is working");
                } else {
                        $failed = true;
                        $this->flash->error("Catalog is not working");
                }

                if ($this->_options['verbose'] || isset($failed)) {
                        $this->flash->notice(print_r($catalog->getResult(), true));
                }
        }

        /**
         * Check database service (read/write and online status).
         */
        private function checkDatabase()
        {
                $database = new DatabaseDiagnostics();

                if ($database->isOnline()) {
                        $this->flash->success("Database is online");
                } else {
                        $failed = true;
                        $this->flash->error("Database is offline");
                }
                if ($database->isWorking()) {
                        $this->flash->success("Database is working");
                } else {
                        $failed = true;
                        $this->flash->error("Database is not working");
                }

                if ($this->_options['verbose'] || isset($failed)) {
                        $this->flash->notice(print_r($database->getResult(), true));
                }
        }

        /**
         * Check and report online status for hostname.
         * @param string $hostname The server hostname.
         */
        private function checkOnlineStatus($hostname)
        {
                $online = new OnlineStatus($hostname);

                if ($online->checkStatus()) {
                        $this->flash->success("$hostname is online");
                } else {
                        $this->flash->error("$hostname is offline");
                }

                if ($this->_options['verbose']) {
                        $this->flash->notice("Resolved addresses:");
                        foreach ($online->getAddresses() as $addr) {
                                $host = OnlineStatus::getServerName($addr);
                                $this->flash->notice("  $addr\t[$host]");
                        }
                }

                if ($this->_options['verbose'] || $online->hasFailed()) {
                        $this->flash->notice("Server status:");
                        foreach ($online->getResult() as $addr => $status) {
                                if ($status) {
                                        $this->flash->notice("  $addr\t[online]");
                                } else {
                                        $this->flash->warning("  $addr\t[offline]");
                                }
                        }
                }
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'auth', 'catalog', 'database', 'online', 'all');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->_options[$option])) {
                                $this->_options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }

                if ($this->_options['all']) {
                        $this->_options['auth'] = true;
                        $this->_options['catalog'] = true;
                        $this->_options['database'] = true;
                }

                if (is_bool($this->_options['online'])) {
                        $this->_options['online'] = '127.0.0.1';
                }

                print_r($this->_options);
        }

}
