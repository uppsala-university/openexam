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
                $this->dispatcher->forward(array(
                        'action' => 'select'
                ));
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
                $this->checkAccess();

                if (!$this->auth->activate($name, $service)) {
                        return false;
                }

                if (!($auth = $this->auth->getAuthenticator())) {
                        return false;
                }

                $form = $auth->create();
                $this->view->setVar("form", $form);
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
                $this->checkAccess();

                if (!($chain = $this->auth->getChain($service))) {
                        return false;
                }

                $auth = new AuthenticatorChain($chain);
                $form = new LoginSelect($auth);

                $this->view->setVar("form", $form);
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
                $this->checkAccess();

                if (!($auth = $this->auth->getAuthenticator())) {
                        return false;
                }
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
                $this->checkAccess();

                $auth = $this->auth->getAuthenticator();
                $icon = $this->url->get("/img/tick-circle.png");

                $this->view->setTemplateBefore('cardbox');

                $this->view->setVars(array(
                        'auth' => $auth,
                        'icon' => $icon
                ));
        }

        /**
         * Login method discover action.
         * @param string $service The service type (e.g. web).
         */
        public function discoverAction($service = "web")
        {
                $this->checkAccess();

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

                        unset($auth);
                }

                $this->response->setJsonContent($result);
                $this->response->send();
        }

}
