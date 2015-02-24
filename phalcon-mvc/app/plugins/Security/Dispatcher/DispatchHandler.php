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
        private $_subsys;
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
        public function __construct($listener, $dispatcher)
        {
                $this->_listener = $listener;
                $this->_dispatcher = $dispatcher;

                $this->_target = $dispatcher->getControllerName();
                $this->_action = $dispatcher->getActionName();
                $this->_subsys = self::getServiceType(strtolower($this->_target));

                $this->_remote = $this->request->getClientAddress(true);
                $this->_protection = $this->acl->getAccess($this->_target, $this->_action);

                $this->_request = md5(microtime(true));
        }

        public function __get($property)
        {
                if ($property == 'service') {
                        return $this->_subsys;
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Setup user session.
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
                        return;
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
                        $this->auth->activate($this->_session->get('type'), $this->_subsys);
                }

                // 
                // Inject authenticated user:
                // 
                $this->getDI()->set('user', new User($this->_session->get("user")));
        }

        /**
         * Process current dispatch.
         * @return boolean
         */
        public function process()
        {
                $this->logger->auth->debug(sprintf(
                        "Handling %s -> %s [subsys: %s, protection: %s]", $this->_target, $this->_action, $this->_subsys, $this->_protection
                ));

                // 
                // Begin authentication and session handling:
                // 
                $this->_auth = new AuthenticationHandler($this->_listener, $this->_subsys);
                $this->_session = new SessionHandler($this->_listener, $this->_subsys);

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
                        if ($this->_auth->login()) {
                                $this->setup();
                                return true;    // pass thru controller -> action
                        }
                }

                // 
                // Handle logout request:
                // 
                if ($this->_target == "auth" && $this->_action == "logout") {
                        if ($this->_auth->logout($this->_session->get('type'))) {
                                $this->_session->remove();
                                return true;    // pass thru controller -> action
                        }
                }

                // 
                // Check session:
                // 
                if ($this->_session->validate()) {
                        $this->setup();
                        return true;
                } elseif ($this->_session->expired()) {
                        $this->_session->remove();
                }

                // 
                // Check authentication:
                // 
                if ($this->_auth->check()) {
                        $this->setup();
                        return true;
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
                // Redirect web request to login page, nuke other.
                // 
                if ($this->_subsys == 'web' && $this->request->isAjax() == false) {
                        $this->logger->auth->debug(sprintf(
                                "Forwarding %s to login page (auth -> select)", $this->_remote
                        ));
                        $this->dispatcher->forward(
                            array(
                                    "controller" => "auth",
                                    "action"     => "select",
                                    "params"     => array("service" => $this->_subsys),
                                    "namespace"  => "OpenExam\Controllers\Gui"
                        ));
                } else {
                        $this->report('Failed login', $this);
                        return false;
                }
        }

        public function getData()
        {
                return array(
                        'subsys'     => $this->_subsys,
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

        /**
         * Detect the called subsystem (WWW, REST or SOAP).
         * @param string $target The dispatched controller class.
         */
        private static function getServiceType($target)
        {
                if (strpos($target, '\\soap\\')) {
                        return 'soap';
                } elseif (strpos($target, '\\rest\\')) {
                        return 'rest';
                } elseif (strpos($target, '\\ajax\\')) {
                        return 'web';
                } else {
                        return 'web';
                }
        }

}
