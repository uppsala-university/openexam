<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Formlogin.php
// Created: 2014-09-09 11:10:59
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Authenticator;

//use UUP\Authentication\Library\Authenticator\AuthenticatorBase;

/**
 * Formlogin abstract class
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
abstract class FormLogin extends Login
{

        const LOGIN_TYPE = 'onsite';
        
        /**
         * Set login type for driving classes
         */
        public function __construct()
        {
                $this->type(self::LOGIN_TYPE);
        }
        
}
