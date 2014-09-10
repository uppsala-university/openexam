<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Remotelogin.php
// Created: 2014-09-09 11:11:25
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Authenticator;

//use UUP\Authentication\Library\Authenticator\AuthenticatorBase;

/**
 * Remotelogin abstract class
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
abstract class RemoteLogin extends Login
{
        
        const LOGIN_TYPE = 'remote';
        
        /**
         * Set login type for driving classes
         */
        public function __construct()
        {
                $this->type(self::LOGIN_TYPE);
        }
        
}
