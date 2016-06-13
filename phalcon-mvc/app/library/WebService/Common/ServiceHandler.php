<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceHandler.php
// Created: 2015-02-02 13:51:39
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Common;

use OpenExam\Library\Security\User;

/**
 * Base class for service handlers.
 * 
 * The service handler should provide methods that return ServiceResponse
 * objects containing one of the status constants defined in this class.
 * 
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class ServiceHandler
{
        /**
         * Successful request.
         */
        const SUCCESS = 200;
        /**
         * Request partial completed. Needs further user interaction.
         */
        const PENDING = 202;
        /**
         * Undefined object requested.
         */
        const UNDEFINED = 400;
        /**
         * Request denied.
         */
        const FORBIDDEN = 403;
        /**
         * Internal server error.
         */
        const ERROR = 500;

        /**
         * The invoking service request.
         * @var ServiceRequest 
         */
        protected $_request;
        /**
         * @var User 
         */
        protected $_user;

        /**
         * Constructor.
         * @param ServiceRequest $request The service request.
         * @param User $user The logged in user.
         */
        public function __construct($request, $user)
        {
                $this->_request = $request;
                $this->_user = $user;
        }

        /**
         * Get the service request.
         * @return ServiceRequest
         */
        public function getRequest()
        {
                return $this->_request;
        }

}
