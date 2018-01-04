<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    AuthenticationHandler.php
// Created: 2015-02-17 10:40:44
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Dispatcher;

use OpenExam\Library\Catalog\Attribute\Provider as AttributeProvider;
use OpenExam\Library\Security\Exception as SecurityException;
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
                $this->_service = $service == "ajax" ? "web" : $service;
                $this->_remote = $this->request->getClientAddress(true);
        }

        public function __get($property)
        {
                if ($property == 'type') {
                        return $this->auth->getAuthenticator()->name;
                } elseif ($property == 'user') {
                        if ($this->auth->accepted($this->_service)) {        // Required by CAS
                                return $this->auth->getSubject();
                        }
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Check authentication.
         * @param string $method Select specific authentication method.
         * @return boolean Return true if caller is authenticated.
         */
        public function check($method = null)
        {
                if (isset($method)) {
                        $this->auth->activate($method, $this->_service);
                }
                if (!$this->auth->accepted($this->_service)) {
                        $this->logger->auth->debug("Caller is not authenticated");
                        return false;
                } else {
                        $this->logger->auth->notice(sprintf(
                                "User login by %s from %s [%s -> %s]", $this->user, $this->_remote, $this->_service, $this->auth->getAuthenticator()->name
                        ));
                        return true;
                }
        }

        /**
         * Handle user initiated login request.
         */
        public function login()
        {
                if (($method = $this->request->get("auth", "string"))) {
                        // 
                        // Handle auth/select response:
                        // 
                        $this->logger->auth->info(sprintf(
                                "User initiated login using method %s -> %s (select)", $this->_service, $method
                        ));
                } elseif (($method = $this->dispatcher->getParam(0))) {
                        // 
                        // Handle auth/login/<type> request:
                        // 
                        $this->logger->auth->info(sprintf(
                                "User initiated login using method %s -> %s (direct)", $this->_service, $method
                        ));
                } elseif (($method = $this->session->get('method'))) {
                        // 
                        // Handle auth/login return (e.g. CAS):
                        // 
                        $this->logger->auth->info(sprintf(
                                "User initiated login using method %s -> %s (return)", $this->_service, $method
                        ));
                }

                if (!isset($method)) {
                        throw new SecurityException(sprintf(
                            "Invalid login request for service %s", $this->_service
                        ));
                } else {
                        $this->session->set('method', $method);
                }

                // 
                // Trigger user authentication:
                // 
                $this->auth->activate($method, $this->_service);
                $this->auth->login();

                // 
                // Has method attribute storage backend?
                // 
                if (!($backend = $this->attrstor->hasBackend($method))) {
                        return true;
                } else {
                        $backend = $this->attrstor->getBackend($method);
                }

                // 
                // Are current authenticator providing user attributes?
                // 
                if (!($this->auth->getAuthenticator() instanceof AttributeProvider)) {
                        return true;
                }
                if (!$this->auth->getAuthenticator()->hasAttributes()) {
                        return true;
                }
                if (($user = $this->auth->getAuthenticator()->getUser()) == null) {
                        return true;
                }
                if (!$backend->exists($user->principal)) {
                        $user->source = $method;
                        $backend->insert($user);
                }
        }

        /**
         * Handle logout request.
         * @param string $method The authentication method.
         */
        public function logout($method)
        {
                $this->auth->activate($method, $this->_service);
                $this->auth->logout();
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
