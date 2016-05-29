<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    StatusCounter.php
// Created: 2016-05-22 20:58:47
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use Phalcon\Mvc\User\Component;

/**
 * Common base class for performance status counters.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class PerformanceCounter extends Component
{

        /**
         * Performance counter keys.
         * @var array
         */
        protected $_keys;
        /**
         * Performance counter data.
         * @var array
         */
        protected $_data;

        /**
         * Constructor.
         * @param array $data Performance counter data.
         * @param array $keys Performance counter keys.
         */
        public function __construct($data, $keys)
        {
                $this->_data = $data;
                $this->_keys = $keys;
        }

        /**
         * Set performance counter data.
         * @param array $data The counter data.
         */
        public function setData($data)
        {
                $this->_data = $data;
        }

        /**
         * Get performance counter data.
         * @return array
         */
        public function getData()
        {
                return $this->_data;
        }

        /**
         * Get performance counter keys.
         * @return array
         */
        public function getKeys()
        {
                return $this->_keys;
        }

        /**
         * Check if performance caounter exists.
         * @param string $type The counter type.
         * @return boolean
         */
        public function hasCounter($type)
        {
                return isset($this->_keys[$type]);
        }

        /**
         * Get performance counter.
         * @param string $type The counter type.
         */
        public function getCounter($type)
        {
                if (isset($this->_keys[$type])) {
                        $data = $this->_data;
                        $keys = $this->_keys;

                        foreach (array_keys($keys) as $key) {
                                if ($key = 'label' || $key == 'descr' || $key == $type) {
                                        continue;
                                }
                                for ($i = 0; $i < count($data); ++$i) {
                                        unset($data[$i]['data'][$key]);
                                }
                        }

                        return array(
                                'data' => $data,
                                'keys' => $keys
                        );
                }
        }

}
