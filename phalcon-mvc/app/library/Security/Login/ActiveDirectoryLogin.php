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
use Phalcon\Config;
use UUP\Authentication\Authenticator\RequestAuthenticator;
use UUP\Authentication\Validator\LdapBindValidator;

/**
 * Microsoft active directory login.
 * 
 * For supporting authentication against multiple active directories, supply
 * a unique $options array:
 * 
 * $auth = new ActiveDirectoryLogin(
 *      'server.example.com', 636, $config, array(
 *              'form' => array(
 *                      'name' => 'myform',
 *                      'user' => 'user1'
 *              )
 *      )
 * );
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ActiveDirectoryLogin extends RequestAuthenticator implements FormLogin
{

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $server The domain controller.
         * @param int $port The LDAP service port.
         * @param array $options Options for form and LDAP.
         */
        public function __construct($config, $server, $port = 636, $options = array(
                'form' => array(
                        'login' => null,
                        'name'  => null,
                        'user'  => null,
                        'pass'  => null
                ),
                'ldap' => array(
                        LDAP_OPT_REFERRALS        => false,
                        LDAP_OPT_PROTOCOL_VERSION => 3
                )))
        {
                // 
                // These are the default options:
                // 
                $defaults = array(
                        'form' => array(
                                'name' => 'msad',
                                'user' => 'user',
                                'pass' => 'pass'
                        ),
                        'ldap' => array(
                                LDAP_OPT_REFERRALS        => false,
                                LDAP_OPT_PROTOCOL_VERSION => 3
                ));

                // 
                // Merge caller options with default. Using array_merge() is
                // not possible because of numeric keys.
                // 
                foreach ($defaults as $service => $array) {
                        foreach ($array as $key => $val) {
                                if (!isset($options[$service][$key]) || $options[$service][$key] == null) {
                                        $options[$service][$key] = $val;
                                }
                        }
                }

                // 
                // Relocate login URI to match form name:
                // 
                if (!isset($options['form']['login'])) {
                        $options['form']['login'] = $config->application->baseUri . 'auth/form/' . $options['form']['name'];
                }

                if (!strstr($server, "://")) {
                        $server = "ldaps://" . $server;
                }

                $validator = new LdapBindValidator($server, $port, $options['ldap']);
                parent::__construct($validator, $options['form']);
                parent::control(self::sufficient);
                parent::visible(true);
        }

}
