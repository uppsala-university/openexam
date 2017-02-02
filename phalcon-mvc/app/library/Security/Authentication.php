<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Authentication.php
// Created: 2014-08-25 13:44:23
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

use Phalcon\Config;
use UUP\Authentication\Authenticator\Authenticator;
use UUP\Authentication\Authenticator\NullAuthenticator;
use UUP\Authentication\Library\Authenticator\AuthenticatorBase;
use UUP\Authentication\Restrictor\Restrictor;
use UUP\Authentication\Stack\AuthenticatorRequiredException;

/**
 * Authentication handler class.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Authentication implements Authenticator, Restrictor
{

        /**
         * The array of service groups and authenticator chains.
         * @var array 
         */
        private $_chains = array('soap' => array(), 'rest' => array(), 'web' => array(), '*' => array());
        /**
         * Current selected authenticator.
         * @var AuthenticatorBase 
         */
        private $_authenticator;
        /**
         * Currently selected service group.
         * @var string 
         */
        private $_service;
        /**
         * The username normalizer callback.
         * @var callable 
         */
        private $_normalizer;

        /**
         * Constructor.
         * @param array $chains The array of service groups and authenticator chains.
         */
        public function __construct($chains = array())
        {
                $this->_chains = array_merge($this->_chains, $chains);
                $this->_authenticator = new NullAuthenticator();
                $this->_service = null;
        }

        /**
         * Add authenticator to stack.
         * 
         * The $name parameter is a string used to identified the authenticator 
         * plugin, e.g. 'cas' or 'kerberos'. The $auth parameter is the wrapped
         * authenticator object itself:
         * 
         * <code>
         * $auth = array(
         *      'method' => function($name, $desc) {
         *              return (new CasLogin(
         *                      'cas.example.com', 443, '/cas'
         *              ))
         *              ->description($desc)
         *              ->name($name);
         *      },
         *      'desc' => 'CAS Login'
         * );
         * </code>
         * 
         * The $service is a key used to associate a group of auth plugins 
         * for a service, e.g. 'soap' or 'web'. If $service parameter is
         * missing, then the authenticator is placed in the common group.
         * 
         * @param string $name The identifier for the authenticator plugin.
         * @param array $auth The authentication plugin.
         * @param string $service The service group associated with this plugin.
         */
        public function add($name, $auth, $service = "*")
        {
                $this->_chains[$service][$name] = $auth;
        }

        /**
         * Remove authenticator from stack.
         * @param string $name The identifier for the authenticator plugin.
         * @param string $service The service group associated with this plugin.
         */
        public function remove($name, $service)
        {
                unset($this->_chains[$service][$name]);
        }

        /**
         * Get all authenticator chains.
         * @return array
         */
        public function getChains()
        {
                return $this->_chains;
        }

        /**
         * Get chain of authenticators for service group.
         * @param string $service The service group (e.g. soap).
         * @return Config
         */
        public function getChain($service = "*")
        {
                return new Config(array_merge($this->_chains[$service], $this->_chains['*']));
        }

        /**
         * Get current active authenticator.
         * @return AuthenticatorBase
         */
        public function getAuthenticator()
        {
                return $this->_authenticator;
        }

        /**
         * Get current active service.
         * @return string
         */
        public function getService()
        {
                return $this->_service;
        }

        /**
         * Get subject for authenticated user.
         * @return string
         */
        public function getSubject()
        {
                return $this->_authenticator->getSubject();
        }

        /**
         * Trigger login action.
         */
        public function login()
        {
                $this->_authenticator->login();
        }

        /**
         * Trigger logout action.
         */
        public function logout()
        {
                $this->_authenticator->logout();
        }

        /**
         * Activate this authentication plugin for next call to login().
         * 
         * @param string $name The identifier for the authenticator plugin.
         * @param string $service The service group associated with this plugin.
         * @return boolean
         */
        public function activate($name, $service = '*')
        {
                if (!isset($this->_chains[$service])) {
                        $service = '*';
                }
                if (!isset($this->_chains[$service][$name])) {
                        return false;
                }

                $auth = $this->_chains[$service][$name]['method']();
                $desc = $this->_chains[$service][$name]['desc'];

                if (!isset($auth)) {
                        return false;
                } else {
                        $this->enable($name, $service, $desc, $auth);
                        return true;
                }
        }

        /**
         * Enable this authenticator.
         * @param string $name The identifier for the authenticator plugin.
         * @param string $service The service group associated with this plugin.
         * @param string $desc The authenticator description.
         * @param AuthenticatorBase $auth The authenticator plugin.
         */
        private function enable($name, $service, $desc, $auth)
        {
                $this->_authenticator = $auth;
                $this->_authenticator->name($name);
                $this->_authenticator->description($desc);
                if (isset($this->_normalizer)) {
                        $this->_authenticator->setNormalizer($this->_normalizer);
                }
                $this->_service = $service;
        }

        /**
         * Authenticate caller.
         * @param string $service The service group.
         * @return bool
         */
        public function accepted($service = null)
        {
                if (!$this->_authenticator->accepted()) {
                        $this->authenticate('*');
                        $this->authenticate($service);
                }

                return $this->_authenticator->accepted();
        }

        /**
         * Try to authenticate caller.
         * @param string $service The service group.
         * @throws AuthenticatorRequiredException
         */
        private function authenticate($service)
        {
                if (isset($this->_chains[$service])) {
                        foreach ($this->_chains[$service] as $name => $plugin) {
                                $authenticator = $plugin['method']();
                                $authenticator->name($name);
                                if ($authenticator->control === Authenticator::REQUIRED) {
                                        if (!$authenticator->accepted()) {
                                                throw new AuthenticatorRequiredException($authenticator->_authenticator);
                                        }
                                }
                                if ($authenticator->control === Authenticator::SUFFICIENT) {
                                        if ($authenticator->accepted() &&
                                            $this->_authenticator instanceof NullAuthenticator) {
                                                $this->activate($name, $service);
                                        }
                                }
                        }
                }
        }

        /**
         * Set username normalizer callback.
         * @param callable $normalizer The normalizer callback.
         */
        public function setNormalizer(callable $normalizer)
        {
                $this->_normalizer = $normalizer;
        }

}
