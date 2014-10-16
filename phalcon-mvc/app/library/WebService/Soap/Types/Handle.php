<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Handle.php
// Created: 2014-10-16 04:50:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap\Types;

/**
 * SOAP call handle class.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Handle
{

        /**
         * Username for authentication.
         * @var string 
         */
        public $user;
        /**
         * Password for authentication.
         * @var string 
         */
        public $pass;
        /**
         * Requested role for call.
         * @var string 
         */
        public $role;
        /**
         * Use session authentication.
         * @var boolean
         */
        public $session;

}
