<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Network.php
// Created: 2016-05-31 19:27:21
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Models\Performance;

/**
 * Network performance collector.
 *
 * Collects network statistics from the /proc file system. It works similar 
 * to ifconfig, but without making additional ioctl(). This implementation is
 * Linux specific, possibly between kernel versions too.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Network extends CollectorBase
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 5;
        /**
         * Default interface name.
         */
        const DEFAULT_NAME = null;
        /**
         * The /proc file system file.
         */
        const PROCFILE = '/proc/net/dev';

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
         * Header data.
         * @var array 
         */
        private $_head;
        /**
         * The NIC name.
         * @var string 
         */
        private $_name;

        /**
         * Constructor.
         * @param int $rate The sample rate.
         * @param string|array $name The NIC name.
         */
        public function __construct($rate = 5, $name = null)
        {
                $this->_rate = $rate;
                $this->_name = $name;
                parent::__construct();
        }

        /**
         * Save performance data.
         */
        protected function save()
        {
                foreach ($this->_data as $name => $data) {
                        if (isset($this->_name)) {
                                if (is_string($this->_name) && $name != $this->_name) {
                                        continue;
                                }
                                if (is_array($this->_name) && !in_array($name, $this->_name)) {
                                        continue;
                                }
                        }

                        $model = new Performance();
                        $model->data = $data;
                        $model->mode = Performance::MODE_NETWORK;
                        $model->host = $this->_host;
                        $model->addr = $this->_addr;
                        $model->source = $name;

                        if (!$model->save()) {
                                foreach ($model->getMessages() as $message) {
                                        trigger_error($message, E_USER_ERROR);
                                }
                                return false;
                        }

                        foreach ($this->_triggers as $trigger) {
                                $trigger->process($model);
                        }
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
                if (!file_exists(self::PROCFILE) ||
                    !is_readable(self::PROCFILE)) {
                        return false;
                }
                if (!($content = file(self::PROCFILE, FILE_IGNORE_NEW_LINES))) {
                        return false;
                }

                if (!isset($this->_head)) {
                        $this->prepare($content);
                }

                // 
                // Discard data headers (already set by prepare()):
                // 
                array_shift($content);
                array_shift($content);

                $this->_data = array();

                foreach ($content as $interface) {
                        $data = preg_split('/\s+/', $interface);

                        while (empty($data[0])) {
                                array_shift($data);
                        }

                        $nic = trim($data[0], " :");

                        $this->_data[$nic] = array();
                        $this->_data[$nic][$this->_head['head'][0]] = array_combine($this->_head['data'][0], array_slice($data, $this->_head['data']['offs'][0], $this->_head['data']['size'][0]));
                        $this->_data[$nic][$this->_head['head'][1]] = array_combine($this->_head['data'][1], array_slice($data, $this->_head['data']['offs'][1], $this->_head['data']['size'][1]));
                }

                return true;
        }

        /**
         * Prepare data headers.
         * @param array $content The proc file content.
         */
        private function prepare($content)
        {
                $result = array();

                $header = explode("|", $content[0]);

                // 
                // Insert the receive/transmit header.
                // 
                $result['head'] = array(
                        strtolower(trim($header[1])),
                        strtolower(trim($header[2]))
                );

                // 
                // Explore counter data names:
                // 
                $header = explode("|", $content[1]);

                $result['data'] = array();
                $result['data'][0] = preg_split('/\s+/', $header[1]);
                $result['data'][1] = preg_split('/\s+/', $header[2]);

                $result['data']['size'] = array(
                        count($result['data'][0]),
                        count($result['data'][1])
                );
                $result['data']['offs'] = array(
                        1,
                        count($result['data'][0]) + 1
                );

                $this->_head = $result;
        }

}
