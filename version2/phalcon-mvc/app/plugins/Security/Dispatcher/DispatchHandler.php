<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DispatchHandler.php
// Created: 2015-02-17 10:41:48
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Dispatcher;

use OpenExam\Library\Core\Signup\Unattended as UnattendedSignup;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\User;
use OpenExam\Plugins\Security\Dispatcher\AuthenticationHandler;
use OpenExam\Plugins\Security\Dispatcher\DispatchHelper;
use OpenExam\Plugins\Security\Dispatcher\SessionHandler;
use OpenExam\Plugins\Security\DispatchListener;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\User\Component;

/**
 * Dispatch handler class.
 * 
 * @property-read string $service The service type.
 * 
 * @access private
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DispatchHandler extends Component implements DispatchHelper
{

        /**
         * The dispatch listener.
         * @var DispatchListener 
         */
        private $_listener;
        /**
         * The dispatcher object.
         * @var Dispatcher 
         */
        private $_dispatcher;
        /**
         * The authentication handler.
         * @var AuthenticationHandler 
         */
        private $_auth;
        /**
         * The session handler.
         * @var SessionHandler
         */
        private $_session;
        /**
         * The target sub system (e.g. web).
         * @var type 
         */
        private $_service;
        /**
         * The target controller.
         * @var string 
         */
        private $_target;
        /**
         * The detected controller/action protection (from ACL).
         * @var type 
         */
        private $_protection;
        /**
         * The target action.
         * @var string 
         */
        private $_action;
        /**
         * The remote caller (IP-address).
         * @var string 
         */
        private $_remote;
        /**
         * Unique request ID.
         * @var string 
         */
        private $_request;

        /**
         * Constructor.
         * @param DispatchListener $listener
         * @param Dispatcher $dispatcher
         */
        public function __construct($listener, $dispatcher, $service)
        {
                $this->_listener = $listener;
                $this->_dispatcher = $dispatcher;

                $this->_target = $dispatcher->getControllerName();
                $this->_action = $dispatcher->getActionName();
                $this->_service = $service;

                $this->_remote = $this->request->getClientAddress(true);
                $this->_protection = $this->acl->getAccess($this->_target, $this->_action);

                $this->_request = md5(microtime(true));
        }

        public function __get($property)
        {
                if ($property == 'service') {
                        return $this->_service;
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Redirect caller to target URL.
         */
        private function redirect()
        {
                $return = $this->session->get('return');

                $this->session->remove('return');
                $this->session->remove('method');

                $this->logger->auth->debug(sprintf(
                        "Redirect browser to %s", $return
                ));

                $this->response->redirect($return);
        }

        /**
         * Setup user session.
         * 
         * Return true if dispatched route should be allowed to continue or not.
         * @return boolean 
         */
        private function setup()
        {
                // 
                // Update session if authenticated:
                // 
                if ($this->_auth->user != null) {
                        $this->_session->set('user', $this->_auth->user);
                        $this->_session->set('type', $this->_auth->type);
                }

                // 
                // Return if authenticated user is missing:
                // 
                if (($this->_session->get('user')) == false) {
                        return false;
                }

                // 
                // Refresh session if about to expire:
                // 
                if ($this->_session->expiring()) {
                        $this->_session->register();
                }

                // 
                // Select authenticator:
                // 
                if ($this->_target == "auth") {
                        $this->auth->activate($this->_session->get('type'), $this->_service);
                }

                // 
                // Inject authenticated user:
                // 
                $this->getDI()->set('user', new User($this->_session->get("user")));

                // 
                // Handle impersonation:
                // 
                if ($this->request->has('impersonate')) {
                        if (!$this->user->impersonate($this->request->get('impersonate', "string"))) {
                                $this->_listener->report('Failed impersonate', $this->getData());
                        }
                }

                // 
                // Signup user:
                // 
                if ($this->config->signup->enabled &&
                    $this->config->signup->automatic) {
                        $signup = new UnattendedSignup();
                        $signup->process();
                }

                // 
                // If returning from authentication, redirect browser and 
                // cancel target dispatch, otherwise permit dispatch.
                // 
                if ($this->session->has('return')) {
                        $this->redirect();
                        return false;
                } else {
                        return true;
                }
        }

        /**
         * Process current dispatch.
         * @return boolean
         */
        public function process()
        {
                $this->logger->auth->debug(sprintf(
                        "Handling %s -> %s [subsys: %s, protection: %s]", $this->_target, $this->_action, $this->_service, $this->_protection
                ));

                // 
                // Begin authentication and session handling:
                // 
                $this->_auth = new AuthenticationHandler($this->_listener, $this->_service);
                $this->_session = new SessionHandler($this->_listener, $this->_service);

                // 
                // Handle public action:
                // 
                if ($this->_protection == 'public') {
                        $this->logger->auth->debug(sprintf(
                                "Granted public access to target %s (action %s)", $this->_target, $this->_action
                        ));
                        $this->setup();
                        return true;
                }

                // 
                // Handle user initiated login request:
                // 
                if ($this->_target == "auth" && $this->_action == "login") {
                        $this->_auth->login();
                        return $this->setup();  // pass thru controller -> action
                }

                // 
                // Handle logout request:
                // 
                if ($this->_target == "auth" && $this->_action == "logout") {
                        $this->_auth->logout($this->_session->get('type'));
                        $this->_session->remove();
                        return true;            // pass thru controller -> action
                }

                // 
                // Check session:
                // 
                if ($this->_session->validate()) {
                        return $this->setup();
                } elseif ($this->_session->expired()) {
                        $this->_session->remove();
                }

                // 
                // Check authentication:
                // 
                if ($this->_auth->check()) {
                        return $this->setup();
                } else {
                        $this->session->start();
                }

                // 
                // Return true unless protection is private:
                // 
                if ($this->_protection == 'protected') {
                        $this->logger->auth->debug(sprintf(
                                "Granting protected access to %s", $this->_remote
                        ));
                        return true;
                }

                // 
                // Unauthenticated request for private resource:
                // 
                if ($this->_service == 'web') {
                        // 
                        // Redirect web request to login page. Keep track of
                        // requested URL for redirect on authenticated.
                        // 
                        $this->logger->auth->debug(sprintf(
                                "Forwarding %s to login page (auth -> select)", $this->_remote
                        ));
                        if (!$this->session->has('return')) {
                                $this->session->set('return', $this->request->get('_url'));
                        }
                        $this->dispatcher->forward(
                            array(
                                    "controller" => "auth",
                                    "action"     => "select",
                                    "params"     => array("service" => $this->_service),
                                    "namespace"  => "OpenExam\Controllers\Gui"
                        ));
                        return false;
                } else {
                        // 
                        // Forward exception to requested service for proper
                        // error reporting back to client.
                        // 
                        $this->_listener->report('Failed login', $this->getData());
                        $this->dispatcher->forward(
                            array(
                                    "controller" => $this->_service,
                                    "action"     => "exception",
                                    "params"     => array(new SecurityException("Authentication required", SecurityException::AUTH)),
                                    "namespace"  => "OpenExam\Controllers\Service"
                        ));
                        return false;
                }
        }

        public function getData()
        {
                return array(
                        'subsys'     => $this->_service,
                        'target'     => $this->_target,
                        'action'     => $this->_action,
                        'protection' => $this->_protection,
                        'remote'     => $this->_remote,
                        'auth'       => isset($this->_auth) ? $this->_auth->getData() : null,
                        'session'    => isset($this->_session) ? $this->_session->getData() : null,
                        'request'    => $this->_request
                );
        }

        public function __toString()
        {
                return print_r($this->getData(), true);
        }

}
