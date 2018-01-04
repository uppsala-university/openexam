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
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 5;

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
        public function __construct($rate = 5)
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

                foreach ($this->getTriggers() as $trigger) {
                        $trigger->process($model);
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
