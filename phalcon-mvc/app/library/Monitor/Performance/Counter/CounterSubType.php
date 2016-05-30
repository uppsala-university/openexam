<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CounterSubType.php
// Created: 2016-05-30 03:58:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Sub type of an performance counter.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CounterSubType implements Counter
{

        /**
         * The counter type.
         * @var string 
         */
        private $_type;
        /**
         * The counter keys.
         * @var array 
         */
        private $_keys;
        /**
         * The counter data.
         * @var array 
         */
        private $_data;

        /**
         * Constructor.
         * 
         * @param Counter $parent The parent counter.
         * @param string $type The counter type.
         */
        public function __construct($parent, $type)
        {
                $data = $parent->getData();
                $keys = $parent->getKeys();

                foreach (array_keys($keys) as $key) {
                        if ($key == 'label' ||
                            $key == 'descr' ||
                            $key == $type) {
                                continue;
                        }
                        for ($i = 0; $i < count($data); ++$i) {
                                unset($data[$i]['data'][$key]);
                        }
                }

                $this->_type = $type;
                $this->_keys = $keys;
                $this->_data = $data;
        }

        public function getType()
        {
                return $this->_type;
        }

        public function getData()
        {
                return $this->_data;
        }

        public function getKeys()
        {
                return $this->_keys;
        }

        public function getDescription()
        {
                return $this->_keys['descr'];
        }

        public function getName()
        {
                return $this->_keys['label'];
        }

        public function getTitle()
        {
                return $this->_keys['label'];
        }

        public function hasCounter($type)
        {
                return isset($this->_keys[$type]);
        }

        public function getCounter($type)
        {
                return new CounterSubType($this, $type);
        }

}
