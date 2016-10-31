<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SwamidLogin.php
// Created: 2016-10-31 12:18:56
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use Phalcon\Config;
use UUP\Authentication\Authenticator\RemoteUserAuthenticator;

/**
 * Simple SAML PHP Login (i.e. SWAMID).
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SimpleSamlLogin extends RemoteUserAuthenticator
{

        /**
         * The simple SAML object.
         * @var SimpleSAML_Auth_Simple 
         */
        private $_client;
        /**
         * The user principal name.
         * @var string 
         */
        private $_subject;

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $name The SP name.
         */
        public function __construct($config, $name = 'sp-default', $path = '/usr/share/php/simplesamlphp')
        {
                require_once(sprintf('%s/lib/_autoload.php', $path));
                $this->_client = new \SimpleSAML_Auth_Simple($name);
                $this->return = $config->application->baseUri . "auth/logout";
        }

        public function accepted()
        {
                if ($this->_client->isAuthenticated()) {
                        $this->_subject = $this->_client->getAttributes()['eduPersonPrincipalName'];
                        return true;
                } else {
                        return false;
                }
        }

        public function getSubject()
        {
                return $this->_subject;
        }

        public function login()
        {
                $this->_client->login();
        }

        public function logout()
        {
                $this->_client->logout(array('ReturnTo' => $this->return));
        }

}
