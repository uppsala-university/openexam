<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Cas.php
// Created: 2014-09-09 11:30:18
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Authenticator\RemoteLogins;

use UUP\Authentication\Authenticator\CasAuthenticator,
    OpenExam\Library\Security\Authenticator\RemoteLogin;

/**
 * Wrapper for CAS authenticator
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class CasLogin extends RemoteLogin
{
        
        /**
         * Instantiate CAS authenticator
         * @param type $host
         * @param type $port
         * @param type $path
         * @return \OpenExam\Library\Security\Authenticator\FormLogin\Cas
         */
        public function __construct($host, $port = 443, $path = "/cas")
        {
                // set CasAuthenticator as login authenticator
                $this->loginAuthenticator(new CasAuthenticator($host, $port, $path));
                parent::__construct();
                
                return $this;
        }

}
