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

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Security\Login\Base\FormLogin;
use UUP\Authentication\Authenticator\RequestAuthenticator;
use UUP\Authentication\Validator\LdapBindValidator;

/**
 * Microsoft Active Directory based form login
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class ActiveDirectoryLogin extends FormLogin
{

        /**
         * Constructor.
         * @param string $server The LDAP server to bind with (usually the domain controller).
         * @param int $port The LDAP server port.
         * @param array $options LDAP and form options.
         */
        public function __construct($server, $port = 636, $options = array('form' => array(), 'ldap' => array()))
        {
                $validator = new LdapBindValidator($server, $port, $options['ldap']);
                $authenticator = new RequestAuthenticator($validator, $options['form']);
                parent::__construct($authenticator);
        }

}
    