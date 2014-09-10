<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CasLogin.php
// Created: 2014-09-10 15:38:26
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\LoginTrait;
use OpenExam\Library\Security\Login\Base\RemoteLogin;
use UUP\Authentication\Authenticator\CasAuthenticator;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CasLogin extends CasAuthenticator implements RemoteLogin
{

        use LoginTrait;

        public function __construct($desc, $host, $port = 443, $path = '/cas')
        {
                parent::__construct($host, $port, $path);
                $this->initialize(Base\LoginHandler::form, $desc, 'cas');
        }

}
