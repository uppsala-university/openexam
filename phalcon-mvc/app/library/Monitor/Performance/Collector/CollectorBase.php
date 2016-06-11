<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
         * Save performance data.
         * @return boolean
         */
        abstract protected function save();
}
