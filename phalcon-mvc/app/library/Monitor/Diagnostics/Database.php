<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Database.php
// Created: 2016-05-31 02:28:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

use OpenExam\Library\Monitor\Diagnostics\OnlineStatus;
use OpenExam\Library\Monitor\Diagnostics\ServiceCheck;
use OpenExam\Library\Monitor\Exception;
use Phalcon\Mvc\User\Component;

/**
 * Diagnostics of database connections.
 *
 * In addition to checking that slave and master database (if appropriate) 
 * is online, this class will also perform read and write on the database 
 * service (dbread/dbwrite) to test its fully working.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Database extends Component implements ServiceCheck
{

        /**
         * The check result.
         * @var array 
         */
        private $_result = array(
                'dbread'  => array(
                        'online'  => true,
                        'working' => true
                ),
                'dbwrite' => array(
                        'online'  => true,
                        'working' => true
                ),
                'dbaudit' => array(
                        'online'  => true,
                        'working' => true
                )
        );
        /**
         * True if test has failed.
         * @var boolean 
         */
        private $_failed = false;

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

                // 
                // Use service config for online test.
                // 

                if (!$this->config->dbread) {
                        throw new Exception("The dbread config is missing");
                }
                if (!$this->config->dbwrite) {
                        throw new Exception("The dbwrite config is missing");
                }
                if (!$this->config->dbaudit) {
                        throw new Exception("The dbaudit config is missing");
                }

                $dbr = new OnlineStatus($this->config->dbread->config->host);
                $dbw = new OnlineStatus($this->config->dbwrite->config->host);
                $dba = new OnlineStatus($this->config->dbaudit->config->host);

                if ($dbr->checkStatus()) {
                        $this->_result['dbread']['online'] = $dbr->getResult();
                } else {
                        $this->_result['dbread']['online'] = $dbr->getResult();
                        $this->_failed = true;
                }

                if ($dbw->checkStatus()) {
                        $this->_result['dbwrite']['online'] = $dbw->getResult();
                } else {
                        $this->_result['dbwrite']['online'] = $dbw->getResult();
                        $this->_failed = true;
                }

                if ($dba->checkStatus()) {
                        $this->_result['dbaudit']['online'] = $dba->getResult();
                } else {
                        $this->_result['dbaudit']['online'] = $dba->getResult();
                        $this->_failed = true;
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
                // Use database service for working test.
                // 

                if (!($this->dbwrite->insert("performance", array('io', ''), array('mode', 'data')))) {
                        $this->_result['dbwrite']['working'] = false;
                        $this->_failed = true;
                } else {
                        $id = $this->dbwrite->lastInsertId();
                }

                if (!($result = $this->dbread->query("SELECT * FROM performance WHERE id = $id"))) {
                        $this->_result['dbread']['working'] = false;
                        $this->_failed = true;
                } elseif (isset($id)) {
                        $this->dbwrite->delete("performance", "id = $id");
                }

                if (!($result = $this->dbaudit->query("SELECT COUNT(*) FROM audit"))) {
                        $this->_result['dbaudit']['working'] = false;
                        $this->_failed = true;
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
