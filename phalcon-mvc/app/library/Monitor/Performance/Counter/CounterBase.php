<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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

}
