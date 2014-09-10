<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ActiveDirectoryLogin.php
// Created: 2014-09-10 16:13:37
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\FormLogin;
use UUP\Authentication\Authenticator\Authenticator;
use UUP\Authentication\Authenticator\RequestAuthenticator;
use UUP\Authentication\Validator\LdapBindValidator;

/**
 * Description of ActiveDirectoryLogin
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ActiveDirectoryLogin extends RequestAuthenticator implements FormLogin
{

        public function __construct($server, $port, $options = array('form' => array(), 'ldap' => array()))
        {
                $validator = new LdapBindValidator($server, $port, $options['ldap']);
                parent::__construct($validator, $options['form']);
        }

}
