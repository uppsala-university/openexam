<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Proxy.php
// Created: 2017-01-16 00:04:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Cache;

use Phalcon\Db\AdapterInterface;
use Phalcon\Db\DialectInterface;

/**
 * Abstract database adapter proxy.
 * 
 * Call methods in the database adapter. Solves the problem with missing 
 * methods in the mediator class by proxy them to the wrapped database
 * adapter using magic call.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class Proxy
{

        /**
         * The database adapter.
         * @var AdapterInterface
         */
        protected $_adapter;
        /**
         * The adapter dialect.
         * @var DialectInterface 
         */
        private $_dialect;
        /**
         * The adapter type.
         * @var string 
         */
        private $_type;

        /**
         * Constructor.
         */
        public function __construct(array $descriptor)
        {
                
        }

        public function __call($name, $arguments)
        {
                if ($name == 'gettype') {
                        if (!isset($this->_type)) {
                                return $this->_type = call_user_func_array(array($this->_adapter, $name), $arguments);
                        } else {
                                return $this->_type;
                        }
                } elseif ($name == 'getdialect') {
                        if (!isset($this->_dialect)) {
                                return $this->_dialect = call_user_func_array(array($this->_adapter, $name), $arguments);
                        } else {
                                return $this->_dialect;
                        }
                } else {
                        return call_user_func_array(array($this->_adapter, $name), $arguments);
                }
        }

}
