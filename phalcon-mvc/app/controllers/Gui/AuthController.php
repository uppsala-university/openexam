<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuthController.php
// Created: 2015-02-16 11:12:06
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Form\LoginSelect;
use OpenExam\Library\Security\Login\Base\FormLogin;
use OpenExam\Library\Security\Login\Base\RemoteLogin;
use UUP\Authentication\Stack\AuthenticatorChain;

/**
 * Authentication user interaction controller.
 * 
 * Authentication is handled upstream. This controller handles user interaction
 * like displaying forms and reacting to login/logout events in a better and 
 * clean way.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AuthController extends GuiController
{

        /**
         * Index action.
         */
        public function indexAction()
        {
                $this->dispatcher->forward(array('action' => 'select'));
        }

        /**
         * Login form action.
         * 
         * This action gets called to display a form for form based login
         * using the requested authenticator.
         * 
         * @param string $name The authentication handler name.
         * @param string $service The service type (e.g. web).
         */
        public function formAction($name = null, $service = "web")
        {
                if (!$this->auth->activate($name, $service)) {
                        return false;
                }
                if (($form = $this->auth->getAuthenticator()) != null) {
                        $this->view->setVar("form", $form->create());
                }
        }

        /**
         * Select login method action.
         * 
         * This action gets called to display a form from where the end user
         * can selected prefered authentication method.
         * 
         * @param string $service The service type (e.g. web).
         */
        public function selectAction($service = "web")
        {
                $this->view->setVar("form", new LoginSelect(
                    new AuthenticatorChain($this->auth->getChain($service))
                ));
        }

        /**
         * User login action.
         * 
         * Called upon successful login using any of the available auth
         * methods. The auth service can be used to get a handle on the 
         * active authenticator:
         * 
         * <code>
         * $auth = $this->auth->getAuthenticator();
         * </code>
         */
        public function loginAction()
        {
                $auth = $this->auth->getAuthenticator();
                if ($auth->accepted()) {
                        $this->response->redirect($this->config->session->startPage);
                }
        }

        /**
         * User logout action.
         * 
         * Called upon successful logout using the current activated login 
         * methods. The auth service can be used to get a handle on the 
         * active authenticator:
         * 
         * <code>
         * $auth = $this->auth->getAuthenticator();
         * </code>
         */
        public function logoutAction()
        {
                $auth = $this->auth->getAuthenticator();
                $this->view->setVar('auth', $auth);
                $this->view->setVar('icon', $this->url->get("/img/tick-circle.png"));
        }

        /**
         * Login method discover action.
         * @param string $service The service type (e.g. web).
         */
        public function discoverAction($service = "web")
        {
                $this->view->disable();

                $result = array();
                $chain = $this->auth->getChain($service);

                foreach ($chain as $name => $plugin) {
                        $auth = $plugin['method']();
                        if ($auth instanceof RemoteLogin) {
                                $result[$name] = $chain[$name];
                                $result[$name]['method'] = "remote";
                                $result[$name]['login'] = $this->url->get(sprintf('/auth/login/%s', $name));
                                $result[$name]['params'] = array(
                                        'host' => $auth->hostname(),
                                        'port' => $auth->port(),
                                        'path' => $auth->path()
                                );
                        }
                        if ($auth instanceof FormLogin) {
                                $result[$name] = $chain[$name];
                                $result[$name]['method'] = "form";
                                $result[$name]['login'] = $this->url->get(sprintf('/auth/login/%s', $name));
                                $result[$name]['params'] = array(
                                        'form' => $auth->form(),
                                        'user' => $auth->user(),
                                        'pass' => $auth->pass()
                                );
                        }
                }

                $this->response->setJsonContent($result);
                $this->response->send();
        }

}
