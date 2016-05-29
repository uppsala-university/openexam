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

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Collector\Server\DiskStatisticsCollector;
use OpenExam\Library\Monitor\Performance\Collector\Server\PartitionStatisticsCollector;
use OpenExam\Library\Monitor\Performance\Collector\Server\VirtualMemoryCollector;

/**
 * System performance task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class PerformanceTask extends MainTask implements TaskInterface
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
                        'header'   => 'System performance tool',
                        'action'   => '--performance',
                        'usage'    => array(
                                '--collect --server|--disk[=name]|--part=dev [--rate=sec]',
                                '--query [--server] [--disk] [--part=device]'
                        ),
                        'options'  => array(
                                '--collect' => 'Collect performance statistics.',
                                '--query'   => 'Check performance counters.',
                                '--server'  => 'Show server performance.',
                                '--disk'    => 'Show disk performace.',
                                '--part'    => 'Show partition performance.',
                                '--rate'    => 'The sample rate.',
                                '--verbose' => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Collect disk performance statistics',
                                        'command' => '--collect --disk=sda --rate=5'
                                ),
                                array(
                                        'descr'   => 'Collect system performance statistics',
                                        'command' => '--collect --server --rate=2'
                                ),
                                array(
                                        'descr'   => 'Show all performance counters',
                                        'command' => '--performance'
                                )
                        )
                );
        }

        /**
         * Performace collection action.
         * @param array $params
         */
        public function collectAction($params = array())
        {
                $this->setOptions($params, 'collect');

                if ($this->_options['server']) {
                        $performance = new VirtualMemoryCollector($this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['disk']) {
                        $performance = new DiskStatisticsCollector($this->_options['disk'], $this->_options['rate']);
                        $performance->start();
                } elseif ($this->_options['part']) {
                        $performance = new PartitionStatisticsCollector($this->_options['part'], $this->_options['rate']);
                        $performance->start();
                } else {
                        $this->flash->error("Collection mode was missing, see --help");
                }
        }

        /**
         * Performace counter query action.
         * @param array $params
         */
        public function queryAction($params = array())
        {
                $this->setOptions($params, 'query');

                $performance = new Performance();
                print_r($performance->getSystemCounter()->getKeys());
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
                $this->_options = array('verbose' => false, 'rate' => 10);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'collect', 'query', 'server', 'disk', 'part', 'rate');
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
        }

}
