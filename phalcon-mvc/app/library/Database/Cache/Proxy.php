<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
