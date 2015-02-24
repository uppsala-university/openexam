<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuthenticationHandler.php
// Created: 2015-02-17 10:40:44
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Dispatcher;

use OpenExam\Plugins\Security\Dispatcher\DispatchHelper;
use OpenExam\Plugins\Security\DispatchListener;
use Phalcon\Mvc\User\Component;

/**
 * Authentication handling class.
 * 
 * @property-read string $type The authentication method.
 * @property-read string $user The authenticated user.
 * 
 * @access private
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AuthenticationHandler extends Component implements DispatchHelper
{

        /**
         * The dispatch listener class.
         * @var DispatchListener
         */
        private $_listener;
        /**
         * The service type (e.g. soap).
         * @var string 
         */
        private $_service;
        /**
         * The remote caller address.
         * @var string 
         */
        private $_remote;

        /**
         * Constructor.
         * @param DispatchListener The dispatch listener.
         * @param string $service The service type.
         */
        public function __construct($listener, $service)
        {
                $this->_listener = $listener;
                $this->_service = $service;
                $this->_remote = $this->request->getClientAddress(true);
        }

        public function __get($property)
        {
                if ($property == 'type') {
                        return $this->auth->getAuthenticator()->name;
                } elseif ($property == 'user') {
                        $this->auth->accepted();        // Required by CAS
                        return $this->auth->getSubject();
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Check authentication.
         * @return boolean Return true if caller is authenticated.
         */
        public function check($method = null)
        {
                if (isset($method)) {
                        $this->auth->activate($method, $this->_service);
                }
                if (!$this->auth->accepted($this->_service)) {
                        $this->logger->auth->debug(sprintf(
                                "Caller is not authenticated (from %s)", $this->_remote
                        ));
                        return false;
                } else {
                        $this->logger->auth->notice(sprintf(
                                "User login by %s from %s [%s -> %s]", $this->user, $this->_remote, $this->_service, $method
                        ));
                        $this->redirect();
                        return true;
                }
        }

        /**
         * Handle user initiated login request.
         * @param string $service The sub system (e.g. web).
         * @return boolean
         */
        public function login()
        {
                if (($method = $this->dispatcher->getParam(0))) {
                        // 
                        // Handle auth/login/<type> request:
                        // 
                        $this->logger->auth->debug(sprintf(
                                "User initiated login using method %s -> %s (direct)", $this->_service, $method
                        ));
                        if (!$this->session->has('return')) {
                                $this->session->set('return', $this->url->get("auth/login"));
                        }
                } elseif (($method = $this->request->get("auth", "string"))) {
                        // 
                        // Handle auth/select response:
                        // 
                        $this->logger->auth->debug(sprintf(
                                "User initiated login using method %s -> %s (select)", $this->_service, $method
                        ));
                } elseif (($method = $this->session->get('method'))) {
                        // 
                        // Handle auth/login return (e.g. CAS):
                        // 
                        $this->logger->auth->debug(sprintf(
                                "User initiated login using method %s -> %s (return)", $this->_service, $method
                        ));
                } else {
                        $this->logger->auth->debug(sprintf(
                                "Invalid login request for service %s", $this->_service
                        ));
                        return false;
                }

                // 
                // Keep redirect URL:
                // 
                if (!$this->session->has('return')) {
                        $this->session->set('return', $this->request->getHTTPReferer());
                }
                if (!$this->session->has('method')) {
                        $this->session->set('method', $method);
                }

                // 
                // Trigger user authentication:
                // 
                $this->auth->activate($method, $this->_service);
                $this->auth->login();
                return false;
        }

        /**
         * Handle logout request.
         * @param string $service The sub system (e.g. web).
         * @return boolean
         */
        public function logout($method)
        {
                $this->auth->activate($method, $this->_service);
                $this->auth->logout();

                return true;
        }

        /**
         * Redirect caller to target URL.
         */
        private function redirect()
        {
                if ($this->_service == "web") {
                        if (($method = $this->session->get('method'))) {
                                $this->session->remove('method');
                        }
                        if (($return = $this->session->get('return'))) {
                                $this->logger->auth->debug(sprintf(
                                        "Redirect browser to %s", $return
                                ));
                                $this->session->remove('return');
                                header(sprintf("Location: %s", $return));
                                $this->logger->auth->debug(sprintf(
                                        "Session: %s", print_r($_SESSION, true)
                                ));
                        }
                }
        }

        public function getData()
        {
                return array(
                        'type'    => $this->type,
                        'user'    => $this->user,
                        'remote'  => $this->_remote,
                        'service' => $this->_service
                );
        }

        public function __toString()
        {
                return print_r($this->getData(), true);
        }

}
