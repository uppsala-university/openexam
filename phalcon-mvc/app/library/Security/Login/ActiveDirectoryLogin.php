<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    ActiveDirectoryLogin.php
// Created: 2014-09-10 16:13:37
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Form\UserLoginForm;
use OpenExam\Library\Security\Login\Base\FormLogin;
use OpenExam\Library\Security\Login\Base\RemoteLogin;
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
 *      $config, 'server.example.com', 636, array(
 *              'form' => array(
 *                      'name' => 'myform',
 *                      'user' => 'user1'
 *              )
 *      )
 * );
 * 
 * A second key ('ldap') in options array can be used for passing LDAP options. 
 * Use the numeric value of LDAP option as array keys. 
 * 
 * Supports default user domain for unqualified usernames, see user->domain in 
 * the system config. 
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ActiveDirectoryLogin extends RequestAuthenticator implements FormLogin, RemoteLogin
{

        /**
         * The server name.
         * @var string 
         */
        private $_server;
        /**
         * The server port.
         * @var int 
         */
        private $_port;

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $server The domain controller.
         * @param int $port The LDAP service port.
         * @param array $options Options for form and LDAP.
         */
        public function __construct($config, $server, $port = 636, $options = null)
        {
                if (!isset($options)) {
                        $options = array(
                                'form' => array(
                                        'login' => null,
                                        'name'  => null,
                                        'user'  => null,
                                        'pass'  => null
                                )
                        );
                }

                // 
                // Keep for future reference:
                // 
                $this->_server = $server;
                $this->_port = $port;

                // 
                // These are the default options:
                // 
                $defaults = array(
                        'form' => array(
                                'name'   => 'msad',
                                'user'   => 'user',
                                'pass'   => 'pass',
                                'domain' => $config->user->domain
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
                        if (!isset($options[$service])) {
                                $options[$service] = array();
                        }
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
                if (!isset($options['form']['return'])) {
                        $options['form']['return'] = false;     // Using return in session
                }

                if (!strstr($server, "://")) {
                        $server = "ldaps://" . $server;
                }

                $validator = new LdapBindValidator($server, $port, $options['ldap']);
                parent::__construct($validator, $options['form']);
                parent::control(self::SUFFICIENT);
                parent::visible(true);
        }

        /**
         * The form name or null.
         * @return string
         */
        public function form()
        {
                return $this->name;
        }

        /**
         * The password request parameter name.
         * @return string
         */
        public function pass()
        {
                return $this->pass;
        }

        /**
         * The username request parameter name.
         * @return string
         */
        public function user()
        {
                return $this->user;
        }

        /**
         * Get hostname of remote login server.
         * @return string
         */
        public function hostname()
        {
                return $this->_server;
        }

        /**
         * Get port of remote login server.
         * @return string
         */
        public function port()
        {
                return $this->_port;
        }

        /**
         * Get remote path.
         * 
         * LDAP don't uses path and will allways return null.
         * 
         * @return string
         */
        public function path()
        {
                return null;
        }

        /**
         * Creates the user login form.
         * @return UserLoginForm
         */
        public function create()
        {
                return new UserLoginForm($this);
        }

}
