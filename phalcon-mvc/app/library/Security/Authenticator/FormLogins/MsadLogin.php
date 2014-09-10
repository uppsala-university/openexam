<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Remotelogin.php
// Created: 2014-09-09 11:15:10
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Authenticator\Formlogins;

use UUP\Authentication\Authenticator\RequestAuthenticator,
    OpenExam\Library\Security\Authenticator\FormLogin;

/**
 * Microsoft Active Directory based form login
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class MsadLogin extends Formlogin
{

        /**
         * Instantiate Active directory authenticator
         * @param type $validator
         * @param type $options
         * @return \OpenExam\Library\Security\Authenticator\Formlogins\MsadLogin
         */
        public function __construct($validator, $options = array())
        {
                // set RequestAuthenticator as authenticator
                $this->loginAuthenticator (new RequestAuthenticator($validator, $options));
                parent::__construct();
                
                return $this;
        }

}
