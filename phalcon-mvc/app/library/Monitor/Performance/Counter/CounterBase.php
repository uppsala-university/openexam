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
// File:    CounterBase.php
// Created: 2016-05-30 06:50:41
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance;
use OpenExam\Library\Monitor\Performance\Counter;
use Phalcon\Mvc\User\Component;

/**
 * Base class for counters.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class CounterBase extends Component implements Counter
{

        /**
         * The counter type.
         * @var string 
         */
        private $_type;
        /**
         * The performance object.
         * @var Performance 
         */
        protected $_performance;

        /**
         * Constructor.
         * @param string $type The counter type.
         * @param Performance $performance The performance object.
         */
        protected function __construct($type, $performance)
        {
                $this->_type = $type;
                $this->_performance = $performance;
        }

        /**
         * Get counter type.
         * @return string
         */
        public function getType()
        {
                return $this->_type;
        }

        /**
         * Get counter data.
         * @return array
         */
        public function getData()
        {
                $filter = $this->_performance->getFilter();
                $limits = $this->_performance->getLimits();

                return CounterQuery::getData($this->_type, $filter, $limits);
        }

        /**
         * Get sub counter.
         * @param string $type The sub counter type.
         * @return Counter
         */
        public function getCounter($type)
        {
                return new CounterSubType($this, $type);
        }

        /**
         * Check if counter uses source field.
         * 
         * The standard behavior is to not supports sources and always 
         * return null.
         * 
         * @return array
         */
        public function getSources()
        {
                return null;
        }

        /**
         * Check if counter uses source field.
         * 
         * The standard behavior is to not supports sources and always 
         * return false.
         * 
         * @return boolean
         */
        public function hasSource()
        {
                return false;
        }

        /**
         * Get all addresses for this counter grouped by address and hostname.
         * @return array
         */
        public function getAddresses()
        {
                return CounterQuery::getAddresses($this->_type);
        }

}
