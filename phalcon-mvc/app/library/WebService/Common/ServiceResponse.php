<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceResponse.php
// Created: 2015-01-29 10:50:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Common;

/**
 * Generic representation of a web service response.
 * 
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ServiceResponse
{

        /**
         * The request handler.
         * @var ServiceHandler 
         */
        public $handler;
        /**
         * The HTTP status code.
         * @var int
         */
        public $status;
        /**
         * The request result.
         * @var mixed 
         */
        public $result;

        /**
         * Constructor.
         * @param ServiceHandler $handler The request handler.
         * @param int $status The HTTP status code.
         * @param mixed $result The request result.
         */
        public function __construct($handler, $status, $result)
        {
                $this->handler = $handler;
                $this->status = $status;
                $this->result = $result;
        }

}
