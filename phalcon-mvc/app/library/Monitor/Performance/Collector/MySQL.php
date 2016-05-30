<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    MySQL.php
// Created: 2016-05-30 18:25:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Models\Performance;

/**
 * MySQL performance collector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class MySQL extends CollectorBase
{

        /**
         * Exit flag.
         * @var boolean 
         */
        private $_done = false;
        /**
         * The sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * The sample data.
         * @var array 
         */
        private $_data;

        /**
         * Constructor.
         * @param int $rate The sample rate.
         */
        public function __construct($rate = 10)
        {
                $this->_rate = $rate;
                parent::__construct();
        }

        /**
         * Save performance data.
         */
        protected function save()
        {
                $model = new Performance();
                $model->data = $this->_data;
                $model->mode = Performance::MODE_MYSQL;
                $model->host = $this->_host;
                $model->addr = $this->_addr;

                if (!$model->save()) {
                        foreach ($model->getMessages() as $message) {
                                trigger_error($message, E_USER_ERROR);
                        }
                        return false;
                }

                return true;
        }

        /**
         * Start collector.
         */
        public function start()
        {
                while (!$this->_done) {
                        if ($this->collect()) {
                                $this->save();
                        }
                        sleep($this->_rate);
                }
        }

        /**
         * Stop collector.
         */
        public function stop()
        {
                $this->_done = true;
        }

        /**
         * Collect performance data.
         * @return bool
         */
        private function collect()
        {
                if (!($result = $this->dbread->query("SHOW GLOBAL STATUS"))) {
                        return false;
                }

                $data = array(
                        'queries'     => array(),
                        'transaction' => array(),
                        'threads'     => array(),
                        'connections' => array(),
                        'transfer'    => array(),
                        'aborted'     => array()
                );

                while (($row = $result->fetch())) {
                        switch ($row[0]) {
                                case 'Aborted_clients':
                                        $data['aborted']['clients'] = $row[1];
                                        break;
                                case 'Aborted_connects':
                                        $data['aborted']['connects'] = $row[1];
                                        break;
                                case 'Bytes_received':
                                        $data['transfer']['bytes-recv'] = $row[1];
                                        break;
                                case 'Bytes_sent':
                                        $data['transfer']['bytes-sent'] = $row[1];
                                        break;
                                case 'Slow_queries':
                                        $data['queries']['slow'] = $row[1];
                                        break;
                                case 'Com_delete':
                                        $data['queries']['delete'] = $row[1];
                                        break;
                                case 'Com_insert':
                                        $data['queries']['insert'] = $row[1];
                                        break;
                                case 'Com_select':
                                        $data['queries']['select'] = $row[1];
                                        break;
                                case 'Com_update':
                                        $data['queries']['update'] = $row[1];
                                        break;
                                case 'Com_begin':
                                        $data['transaction']['begin'] = $row[1];
                                        break;
                                case 'Com_commit':
                                        $data['transaction']['commit'] = $row[1];
                                        break;
                                case 'Com_rollback':
                                        $data['transaction']['rollback'] = $row[1];
                                        break;
                                case 'Connections':
                                        $data['connections']['total'] = $row[1];
                                        break;
                                case 'Max_used_connections':
                                        $data['connections']['max-used'] = $row[1];
                                        break;
                                case 'Threads_cached':
                                        $data['threads']['cached'] = $row[1];
                                        break;
                                case 'Threads_connected':
                                        $data['threads']['connected'] = $row[1];
                                        break;
                                case 'Threads_created':
                                        $data['threads']['created'] = $row[1];
                                        break;
                                case 'Threads_running':
                                        $data['threads']['running'] = $row[1];
                                        break;
                        }
                }

                $this->_data = $data;
                return true;
        }

}
