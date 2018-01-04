<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    DocumentLiteral.php
// Created: 2014-10-16 03:13:46
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap\Wrapper;

use OpenExam\Library\WebService\Soap\SoapHandler;

/**
 * Document literal wrapper class.
 * 
 * Wrapper for SOAP method call supporting standard access to arguments and
 * normal way of returning method result. 
 * 
 * In non-wrapped mode, the argument is accessed thru a standard PHP object 
 * and return values needs to be set in a returned array:
 * 
 * <code>
 * public function add($num1, $num2)
 * {
 *      $sum = $num1->num1 + $num1->num2;       // All args in $num1
 *      return array('return' => $sum);         // Must use array
 * }
 * </code>
 * 
 * In wrapped mode, the method arguments are accessed as normal parameters 
 * and the return value can be returned as usual:
 * 
 * <code>
 * public function add($num1, $num2)
 * {
 *      $sum = $num1 + $num2;
 *      return $sum;
 * }
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DocumentLiteral implements SoapHandler
{

        /**
         * The target SOAP service.
         * @var SoapHandler 
         */
        private $_service;

        /**
         * Constructor.
         * @param SoapHandler $service The wrapped SOAP service object.
         */
        public function __construct($service)
        {
                $this->_service = $service;
        }

        public function __call($name, $arguments)
        {
                $response = call_user_func_array(
                    array($this->_service, $name), (array) ($arguments[0])
                );
                return array('return' => $response);
        }

}
