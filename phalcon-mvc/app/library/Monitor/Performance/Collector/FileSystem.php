<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    FileSystem.php
// Created: 2016-06-01 15:02:06
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Console\Command;
use OpenExam\Models\Performance;

/**
 * File system info collector.
 *
 * Collect performance data every 5 minute for common file systems:
 * <code>
 * $collector = new FileSystem(300, null, array('ext4', 'xfs', 'btrfs');
 * $collector->start();
 * </code>
 * 
 * The default behavior is to include all file systems having a device
 * name starting with '/' (e.g. /dev/sda1):
 * <code>
 * $collector = new FileSystem();
 * $collector->start();
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class FileSystem extends CollectorBase
{

        /**
         * Suggested default sample rate.
         */
        const SAMPLE_RATE = 60;
        /**
         * Default file system path.
         */
        const DEFAULT_PATH = null;
        /**
         * Default file system type.
         */
        const DEFAULT_TYPE = null;

        /**
         * Exit flag.
         * @var boolean 
         */
        private $_done = false;
        /**
         * Sample rate.
         * @var int 
         */
        private $_rate;
        /**
         * File system path.
         * @var string|array
         */
        private $_path;
        /**
         * Type of file system to include.
         * @var string 
         */
        private $_type;
        /**
         * The sample data.
         * @var array 
         */
        private $_data;

        /**
         * Constructor.
         * 
         * @param int $rate The sample rate.
         * @param string|array $path The file system path (e.g. '/home').
         * @param string|array $type The type of file systems (e.g. 'ext4').
         */
        public function __construct($rate = 60, $path = null, $type = null)
        {
                if (isset($path)) {
                        if (is_string($path) && strstr($path, ':')) {
                                $path = explode(':', $path);
                        }
                }
                if (isset($type)) {
                        if (is_string($type) && strstr($type, ':')) {
                                $type = explode(':', $type);
                        }
                }

                $this->_rate = $rate;
                $this->_path = $path;
                $this->_type = $type;

                parent::__construct();

                $this->detect();
                $this->validate();
        }

        /**
         * Save performance data.
         */
        protected function save()
        {
                foreach ($this->_data as $dir => $data) {
                        $model = new Performance();
                        $model->data = array('usage' => $data);
                        $model->mode = Performance::MODE_FILESYS;
                        $model->host = $this->_host;
                        $model->addr = $this->_addr;
                        $model->source = $dir;

                        if (!$model->save()) {
                                foreach ($model->getMessages() as $message) {
                                        trigger_error($message, E_USER_ERROR);
                                }
                                return false;
                        }

                        foreach ($this->getTriggers($model->source) as $trigger) {
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
         * Validate array of mounted file systems.
         */
        private function validate()
        {
                foreach ($this->_path as $dev => $dir) {
                        if (!file_exists($dir)) {
                                unset($this->_path[$dev]);
                        }
                }
        }

        /**
         * Prepare for data collection. If path is null, then dynamic discover
         * file systems using mount.
         */
        private function detect()
        {
                if (is_string($this->_path)) {
                        $this->_path = array($this->_path);
                }
                if (is_array($this->_path)) {
                        return;
                }

                $command = new Command("mount");
                $command->execute();

                $this->_path = array();

                foreach ($command->getOutput() as $line) {
                        $parts = preg_split('/\s+/', $line);

                        if (isset($this->_type)) {
                                if (is_array($this->_type)) {
                                        if (!in_array($parts[4], $this->_type)) {
                                                continue;
                                        }
                                }
                                if (is_string($this->_type)) {
                                        if ($this->_type != $parts[4]) {
                                                continue;
                                        }
                                }
                        }

                        if ($parts[0][0] == '/') {
                                $this->_path[$parts[0]] = $parts[2];
                        }
                }
        }

        /**
         * Collect sample data.
         */
        private function collect()
        {
                $data = array();

                foreach ($this->_path as $dir) {
                        $avail = intval(disk_free_space($dir) / (1024 * 1024));
                        $total = intval(disk_total_space($dir) / (1024 * 1024));
                        $data[$dir] = array(
                                'total' => $total,
                                'free'  => $avail,
                                'used'  => $total - $avail
                        );
                }

                $this->_data = $data;
                return true;
        }

}
