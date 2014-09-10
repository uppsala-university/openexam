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

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\RemoteLogin;
use UUP\Authentication\Authenticator\CasAuthenticator;

/**
 * CAS login integration.
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class CasLogin extends RemoteLogin
{

        /**
         * Constructor.
         * @param string $host
         * @param int $port
         * @param string $path
         * @return \OpenExam\Library\Security\Authenticator\FormLogin\Cas
         */
        public function __construct($host, $port = 443, $path = "/cas")
        {
                parent::__construct(new CasAuthenticator($host, $port, $path));
        }

}
