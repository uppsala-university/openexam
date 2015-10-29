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
        private $chains = array('soap' => array(), 'rest' => array(), 'web' => array(), '*' => array());
        /**
         * Current selected authenticator.
         * @var AuthenticatorBase 
         */
        private $authenticator;
        /**
         * Currently selected service group.
         * @var string 
         */
        private $service;

        /**
         * Constructor.
         * @param array $chains The array of service groups and authenticator chains.
         */
        public function __construct($chains = array())
        {
                $this->chains = array_merge($this->chains, $chains);
                $this->authenticator = new NullAuthenticator();
                $this->service = null;
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
                $this->chains[$service][$name] = $auth;
        }

        /**
         * Remove authenticator from stack.
         * @param string $name The identifier for the authenticator plugin.
         * @param string $service The service group associated with this plugin.
         */
        public function remove($name, $service)
        {
                unset($this->chains[$service][$name]);
        }

        /**
         * Get chain of authenticators for service group.
         * @param string $service The service group (e.g. soap).
         * @return array
         */
        public function getChain($service = "*")
        {
                return new Config(array_merge($this->chains[$service], $this->chains['*']));
        }

        /**
         * Get current active authenticator.
         * @return AuthenticatorBase
         */
        public function getAuthenticator()
        {
                return $this->authenticator;
        }

        /**
         * Get current active service.
         * @return string
         */
        public function getService()
        {
                return $this->service;
        }

        /**
         * Get subject for authenticated user.
         * @return string
         */
        public function getSubject()
        {
                return $this->authenticator->getSubject();
        }

        /**
         * Trigger login action.
         */
        public function login()
        {
                $this->authenticator->login();
        }

        /**
         * Trigger logout action.
         */
        public function logout()
        {
                $this->authenticator->logout();
        }

        /**
         * Activate this authentication plugin for next call to login().
         * @param string $name The identifier for the authenticator plugin.
         * @param string $service The service group associated with this plugin.
         */
        public function activate($name, $service = '*')
        {
                if (!isset($this->chains[$service])) {
                        $service = '*';
                }
                if (isset($this->chains[$service][$name])) {
                        $this->enable(
                            $name, $service, $this->chains[$service][$name]['desc'], $this->chains[$service][$name]['method']()
                        );
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
                $this->authenticator = $auth;
                $this->authenticator->name($name);
                $this->authenticator->description($desc);
                $this->service = $service;
        }

        /**
         * Authenticate caller.
         * @param string $service The service group.
         * @return bool
         */
        public function accepted($service = null)
        {
                if (!$this->authenticator->accepted()) {
                        $this->authenticate('*');
                        $this->authenticate($service);
                }

                return $this->authenticator->accepted();
        }

        /**
         * Try to authenticate caller.
         * @param string $service The service group.
         * @throws AuthenticatorRequiredException
         */
        private function authenticate($service)
        {
                if (isset($this->chains[$service])) {
                        foreach ($this->chains[$service] as $name => $plugin) {
                                $authenticator = $plugin['method']();
                                $authenticator->name($name);
                                if ($authenticator->control === Authenticator::required) {
                                        if (!$authenticator->accepted()) {
                                                throw new AuthenticatorRequiredException($authenticator->authenticator);
                                        }
                                }
                                if ($authenticator->control === Authenticator::sufficient) {
                                        if ($authenticator->accepted() &&
                                            $this->authenticator instanceof NullAuthenticator) {
                                                $this->activate($name, $service);
                                        }
                                }
                        }
                }
        }

}
