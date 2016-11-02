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
use SimpleSAML_Auth_Simple;
use UUP\Authentication\Authenticator\Authenticator;
use UUP\Authentication\Library\Authenticator\AuthenticatorBase;
use UUP\Authentication\Restrictor\Restrictor;

/**
 * Simple SAML PHP Login (i.e. discovery in SWAMID).
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SimpleSamlLogin extends AuthenticatorBase implements Restrictor, Authenticator
{

        /**
         * The user principal attribute name.
         */
        const PRINCIPAL = 'eduPersonPrincipalName';
        /**
         * The default session name (see simplesamlphp config).
         */
        const SESSION_NAME = 'simplesaml';

        /**
         * The simple SAML object.
         * @var SimpleSAML_Auth_Simple 
         */
        private $_client;
        /**
         * Target base URI.
         * @var string 
         */
        private $_target;
        /**
         * The login/logout URL.
         * @var string 
         */
        private $_return;

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param string $name The SP name.
         */
        public function __construct($config, $name = 'default-sp', $path = null)
        {
                $this->requires('lib/_autoload.php', $path);

                $this->_client = new SimpleSAML_Auth_Simple($name);
                $this->_target = $config->application->baseUri;
        }

        public function accepted()
        {
                $this->invoke();
                $result = $this->_client->isAuthenticated();
                $this->leave();
                return $result;
        }

        public function getSubject()
        {
                $this->invoke();
                $result = $this->_client->getAttributes()[self::PRINCIPAL][0];
                $this->leave();
                return $result;
        }

        public function attributes()
        {
                $this->invoke();
                $result = $this->_client->getAttributes();
                $this->leave();
                return $result;
        }

        public function login()
        {
                $this->invoke();
                $this->_return = sprintf("%s/auth/login", $this->_target);
		$this->_client->requireAuth(array('ReturnTo' => $this->_return));
                $this->leave();
        }

        public function logout()
        {
                $this->invoke();
                $this->_return = sprintf("%s/auth/logout", $this->_target);
                $this->_client->logout(array('ReturnTo' => $this->_return));
                $this->leave();
        }

        private function invoke()
        {
                $this->_status = session_status();

                if (session_status() == PHP_SESSION_ACTIVE &&
                    session_status() != PHP_SESSION_DISABLED) {
                        session_write_close();
                }
                if (session_status() == PHP_SESSION_NONE &&
                    session_status() != PHP_SESSION_DISABLED) {
                        session_start();
                }
        }

        private function leave()
        {
                if (session_status() == PHP_SESSION_ACTIVE &&
                    session_status() != PHP_SESSION_DISABLED) {
                        session_write_close();
                }
                if ($this->_status == PHP_SESSION_ACTIVE &&
                    session_status() == PHP_SESSION_NONE) {
                        session_start();
                }
        }

        private function requires($file, $path = null)
        {
                $locations = array(
                        '/usr/share/php/simplesamlphp/',
                        __DIR__ . '/../../../../../../', // deployed
                        __DIR__ . '/../../../../vendor/' // package
                );
                if (isset($path)) {
                        if (!in_array($path, $locations)) {
                                $locations[] = $path;
                        }
                }
                foreach ($locations as $location) {
                        if (file_exists($location . $file)) {
                                require_once $location . $file;
                        }
                }
        }

}
