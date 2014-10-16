<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DocumentLiteralWrapper.php
// Created: 2014-10-16 03:13:46
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap;

/**
 * Document literal wrapper class.
 * 
 * This class wraps the SOAP method call supporting normal access to argument.
 * Returning values don't either requiring wrapping in inside a response 
 * object/array.
 * 
 * Heres the difference between non-wrapper and wrapped mode:
 * 
 * <code>
 * // 
 * // In non-wrapped mode (all arguments in first parameter):
 * // 
 * public function method($arg1, $arg2) 
 * {
 *      $arg1->arg1->val1;      // OK
 *      $arg1->arg2->val2;      // OK
 *      $arg2->...              // unset
 * 
 *      return array(           // Return value must be wrapped
 *              'result' => $response
 *      );
 * }
 * 
 * // 
 * // In wrapped mode (arguments are separated):
 * // 
 * public function method($arg1, $arg2) 
 * {
 *      $arg1->val1;            // OK
 *      $arg2->val2;            // OK
 * 
 *      return $response;       // Return value don't need to be wrapped.
 * }
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DocumentLiteralWrapper implements SoapHandler
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
