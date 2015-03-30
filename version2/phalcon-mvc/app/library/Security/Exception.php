<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Exception.php
// Created: 2014-09-02 23:15:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Security;

class Exception extends \Exception
{

        /**
         * Failed aquire role.
         */
        const ROLE = 1;
        /**
         * Caller are not the owner of requested object.
         */
        const OWNER = 2;
        /**
         * Access is denied.
         */
        const ACCESS = 3;
        /**
         * Action is not allowed.
         */
        const ACTION = 4;
        /**
         * The ACL service is missing.
         */
        const ACL = 5;
        /**
         * The user service is missing.
         */
        const USER = 6;
        /**
         * Caller is not authenticated.
         */
        const AUTH = 7;

}
