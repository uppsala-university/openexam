<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        private $service;

        /**
         * Constructor.
         * @param SoapHandler $service The wrapped SOAP service object.
         */
        public function __construct($service)
        {
                $this->service = $service;
        }

        public function __call($name, $arguments)
        {
                $response = call_user_func_array(
                    array($this->service, $name), (array) ($arguments[0])
                );
                return array('return' => $response);
        }

}
