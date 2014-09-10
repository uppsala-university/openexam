<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LoginHandler.php
// Created: 2014-09-10 15:29:41
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

/**
 * Decorates AuthenticatorBase with authenticator type (form or remote).
 * 
 * @property-read string $type The type of login (form or remote).
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface LoginHandler
{

        /**
         * Authentication is handled by this system (thru form POST).
         */
        const form = 'form';
        /**
         * Authentication is handled remote (e.g. CAS).
         */
        const remote = 'remote';

        /**
         * Set type of authenticator.
         * @param string $type
         * @return Authenticator
         */
        function type($type);
}
