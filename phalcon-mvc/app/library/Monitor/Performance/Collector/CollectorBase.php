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
// File:    CollectorBase.php
// Created: 2016-05-23 23:08:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Collector;

use OpenExam\Library\Monitor\Performance\Collector;
use OpenExam\Library\Monitor\Performance\Trigger;
use Phalcon\Mvc\User\Component;

/**
 * Abstract base class for performance collectors.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class CollectorBase extends Component implements Collector
{

        /**
         * The address for this server.
         * @var string
         */
        protected $_addr;
        /**
         * The hostname for this server.
         * @var string 
         */
        protected $_host;
        /**
         * The collection of triggers.
         * @var Trigger[] 
         */
        protected $_triggers = array();
        /**
         * Per source shadowed triggers.
         * @var Trigger[]  
         */
        private $_call;

        /**
         * Constructor.
         */
        protected function __construct()
        {
                $this->_addr = gethostbyname(gethostname());
                $this->_host = gethostbyaddr($this->_addr);
        }

        /**
         * Get IP address.
         * @return string
         */
        public function getAddress()
        {
                return $this->_addr;
        }

        /**
         * Get hostname (FQHN).
         * @return string
         */
        public function getHostname()
        {
                return $this->_host;
        }

        /**
         * Add trigger for this collector.
         * @param Trigger $trigger The trigger object.
         */
        public function addTrigger($trigger)
        {
                $this->_triggers[] = $trigger;
        }

        /**
         * Get cloned triggers.
         * @param string $name The source name.
         * @return Trigger[]
         */
        protected function getTriggers($name = null)
        {
                if (!isset($name)) {
                        return $this->_triggers;
                }
                if (!isset($this->_call)) {
                        $this->_call = array();
                }

                if (!isset($this->_call[$name])) {
                        $this->_call[$name] = array();
                        foreach ($this->_triggers as $trigger) {
                                $this->_call[$name][] = clone $trigger;
                        }
                }

                return $this->_call[$name];
        }

        /**
         * Save performance data.
         * @return boolean
         */
        abstract protected function save();
}
