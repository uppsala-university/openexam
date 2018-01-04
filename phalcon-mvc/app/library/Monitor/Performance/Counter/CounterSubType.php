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
         * The parent counter.
         * @var Counter
         */
        private $_parent;

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

                $this->_parent = $parent;
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

        public function getSources()
        {
                return $this->_parent->getSources();
        }

        public function hasSource()
        {
                return $this->_parent->hasSource();
        }

        public function getAddresses()
        {
                return $this->_parent->getAddresses();
        }

}
