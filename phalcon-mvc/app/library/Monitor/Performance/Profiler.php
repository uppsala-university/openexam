<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Profiler.php
// Created: 2016-06-13 01:53:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance;

use OpenExam\Models\Profile;
use Phalcon\Config;
use Phalcon\Mvc\User\Component;

/**
 * System performance profiler.
 * 
 * This profiler is configured in config.def:
 * <code>
 *         'profile'     => array(
 *               'gc'     => true, // Run garbage collection
 *              'memory' => true, // Track memory usage
 *              'time'   => true, // Track time spent
 *              'start'  => 1,    // Start mode (0 == disable, 1 == on demand, 2 == always)
 *              'tlimit' => 0,    // Execution time threshold value (sec as float).
 *              'mlimit' => 0,    // Memory usage threshold value (in bytes).
 *              'atexit' => true  // Time 
 *      ),
 * </code>
 * 
 * It can be used in two ways:
 * 
 * <ol>
 * <li> Call add("name") to add a checkpoint.</li>
 * <li> Call start("name") followed by stop("name") to measure diff.</li>
 * </ol>
 * 
 * Some standard checkpoints added by default:
 * 
 * <ol>
 * <li>request:    The request initial time (from client side).</li>
 * <li>dispatch:   Added in dispatcher.</li>
 * <li>controller: Added by controller.</li>
 * <li>shutdown:   Profiler shutdown.</li>
 * </ol>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Profiler extends Component
{

        /**
         * Profiling has been disabled.
         */
        const START_DISABLED = 0;
        /**
         * Started on demand.
         */
        const START_ON_DEMAND = 1;
        /**
         * Always start profiler.
         */
        const START_ALWAYS = 2;
        /**
         * Permit manual start of profiler.
         */
        const START_MANUAL = 3;
        /**
         * HTTP request header for on demand start.
         */
        const HEADER = 'X-Profile';
        /**
         * Default name.
         */
        const NAME = 'profile';

        /**
         * Initialize time.
         * @var float 
         */
        private $_init;
        /**
         * Last invoke time.
         * @var float 
         */
        private $_last;
        /**
         * The profiler name.
         * @var string
         */
        private $_name = self::NAME;
        /**
         * Profile data.
         * @var array 
         */
        private $_data = array();
        /**
         * The service config.
         * @var Config 
         */
        private $_config;
        /**
         * Enable profiler.
         * @var bool 
         */
        private $_enabled = false;

        /**
         * Constructor.
         * @param Config $config The service config.
         */
        public function __construct($config)
        {
                if (($this->_config = $config)) {
                        if ($this->_config->gc) {
                                gc_enable();
                                gc_collect_cycles();
                        }

                        if (strlen($this->request->getHeader(self::HEADER)) > 0) {
                                $this->_enabled = true;
                        }
                        if ($this->_config->start == self::START_ALWAYS) {
                                error_log(__METHOD__ . ':' . __LINE__);
                                $this->_enabled = true;
                        }
                        if ($this->_config->start == self::START_DISABLED) {
                                error_log(__METHOD__ . ':' . __LINE__);
                                $this->_enabled = false;
                        }

                        error_log($this->_enabled);

                        $this->_init = $this->_last = microtime(true);
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                if (!$this->_config || !$this->_enabled) {
                        return;
                } else {
                        $this->add("shutdown");
                }
                if ($this->_config->gc) {
                        if (gc_enabled()) {
                                gc_disable();
                        }
                }
                if ($this->_config->fair) {
                        $this->_last = microtime(true);
                }
                if (($this->_config->tlimit < $this->_last - $this->_init) ||
                    ($this->_config->mlimit < memory_get_peak_usage())) {
                        $profile = new Profile();

                        $profile->request = $this->request->get("_url");
                        $profile->name = $this->_name;
                        $profile->peak = memory_get_peak_usage();
                        $profile->time = $this->_last - $this->_init;
                        $profile->data = $this->_data;

                        if (!$profile->save()) {
                                foreach ($profile->getMessages() as $message) {
                                        $this->logger->system->error("Failed save profile data ($message)");
                                }
                        }
                }
        }

        /**
         * Enable profiling.
         * @param boolean $value Use false to disable.
         */
        public function enable($value = true)
        {
                if ($this->_config &&
                    $this->_config->start == self::START_MANUAL) {
                        $this->_enabled = $value;
                }
        }

        /**
         * Check if profiling is enabled.
         * @return boolean
         */
        public function enabled()
        {
                return $this->_enabled;
        }

        /**
         * Garbage collection.
         */
        public function destroy()
        {
                $this->_data = null;
                $this->_last = null;
                $this->_name = null;
        }

        /**
         * Set profiler name.
         * @param string $name The name.
         */
        public function setName($name)
        {
                $this->_name = $name;
        }

        /**
         * Get profiler name.
         * @return string
         */
        public function getName()
        {
                return $this->_name;
        }

        /**
         * Get profile result.
         * @param type $name
         * @return boolean
         */
        public function getResult($name = null)
        {
                if (!$this->_enabled) {
                        return false;
                }
                if (isset($name)) {
                        return $this->_data[$name];
                } else {
                        return $this->_data;
                }
        }

        /**
         * Get initialize time.
         * @return float
         */
        public function initial()
        {
                return $this->_init;
        }

        /**
         * Start profile timer.
         * @param string $name The profile entry name.
         */
        public function start($name)
        {
                if ($this->_enabled) {
                        $this->_data[$name] = $this->get();
                }
        }

        /**
         * Stop profile timer.
         * @param string $name The profile entry name.
         */
        public function stop($name)
        {
                if ($this->_enabled) {
                        $prev = $this->_data[$name];
                        $curr = $this->get();

                        $this->_data[$name]['diff'] = self::diff($prev, $curr);
                }
        }

        /**
         * Reset all profile data.
         */
        public function reset()
        {
                $this->_data = array();
        }

        /**
         * Add an checkpoint.
         * 
         * @param string $name The checkpoint name.
         * @param float $time An optional timestamp.
         */
        public function add($name, $time = 0)
        {
                if ($this->_enabled) {
                        if ($time == 0) {
                                $this->_data[$name] = $this->get();
                        } else {
                                $this->_data[$name] = array(
                                        'time' => $time,
                                        'diff' => microtime(true) - $time
                                );
                        }
                }
        }

        /**
         * Get profile data.
         * @return array
         */
        private function get()
        {
                $data = array(
                        'memory' => array(
                                'peak' => 0,
                                'curr' => 0
                        ),
                        'time'   => 0
                );

                if ($this->_config->gc && gc_enabled()) {
                        gc_collect_cycles();
                }
                if ($this->_config->memory) {
                        $data['memory'] = array(
                                'peak' => memory_get_peak_usage(),
                                'curr' => memory_get_usage()
                        );
                }
                if ($this->_config->time) {
                        $data['time'] = microtime(true);
                        $data['diff'] = $data['time'] - $this->_last;
                }

                $this->_last = microtime(true);
                return $data;
        }

        /**
         * Compute diff of two profile data arrays.
         * 
         * @param array $data1
         * @param array $data2
         * @return array
         */
        private static function diff($data1, $data2)
        {
                return array(
                        'memory' => array(
                                'peak' => $data2['memory']['peak'] - $data1['memory']['peak'],
                                'curr' => $data2['memory']['curr'] - $data1['memory']['curr'],
                        ),
                        'diff'   => $data2['time'] - $data1['time']
                );
        }

}
