<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Apache.php
// Created: 2016-05-30 07:37:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Monitor\Performance\Collector\Apache\Connections;
use OpenExam\Library\Monitor\Performance\Collector\Apache\ServerStatus;
use OpenExam\Library\Monitor\Performance\Collector\Apache\Sockets;
use OpenExam\Models\Performance;

/**
 * Apache performance collector.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Apache extends CollectorBase
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 10;
        /**
         * The default user for apache process.
         */
        const DEFAULT_USER = 'apache';

        /**
         * Apache server connections.
         * @var Connections 
         */
        private $_connections;
        /**
         * Apache socket usage.
         * @var Sockets
         */
        private $_sockets;
        /**
         * Apache server status (mod_status).
         * @var ServerStatus 
         */
        private $_status;
        /**
         * Exit flag.
         * @var boolean 
         */
        private $_done = false;
        /**
         * Sample data/result.
         * @var array 
         */
        private $_data;
        /**
         * The sample rate.
         * @var int 
         */
        private $_rate;

        /**
         * Constructor.
         * @param string $user The apache user.
         * @param int $rate The sample rate.
         */
        public function __construct($rate = 10, $user = 'apache')
        {
                parent::__construct();

                $this->_connections = new Connections();
                $this->_sockets = new Sockets($user);
                $this->_status = new ServerStatus();

                $this->_rate = $rate;
        }

        /**
         * Save sample data to performance model.
         * @return boolean
         */
        protected function save()
        {
                $model = new Performance();
                $model->data = $this->_data;
                $model->mode = Performance::MODE_APACHE;
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
         * Start performance collector.
         */
        public function start()
        {
                while (!$this->_done) {

                        $sock = $this->_sockets->getStatus();
                        $conn = $this->_connections->getStatus();
                        $stat = $this->_status->getStatus();

                        $this->_data = array(
                                'socket'         => $sock,
                                'connect-state'  => $conn['state'],
                                'connect-queue'  => $conn['queue'],
                                'status-load'    => $stat['load'],
                                'status-total'   => $stat['total'],
                                'status-cpu'     => $stat['cpu'],
                                'status-request' => $stat['request'],
                                'status-workers' => $stat['workers']
                        );
                        $this->save();
                        sleep($this->_rate);
                }
        }

        /**
         * Stop performance collector.
         */
        public function stop()
        {
                $this->_done = true;
        }

}
