<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuthController.php
// Created: 2014-08-28 09:18:12
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Library\Security;

/**
 * Controller for handling authentication requests through authenticators (e.g CAS)
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class AuthController extends \OpenExam\Controllers\GuiController
{

        /**
         * Redirection URLs after Authentication
         */
        CONST LOGIN_SUCCESS_URL = "exam/index";
        CONST LOGIN_FAILURE_URL = "";

        public function initialize()
        {
                // disable layout for the views
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

                parent::initialize();
        }

        /**
         * Index action
         */
        public function indexAction()
        {
                
        }

        /**
         * Login Action 
         */
        public function loginAction()
        {

                // redirect if already logged in
                if ($this->user->getUser())
                        return $this->response->redirect($loginSuccessUrl);

                // if authentication method is set, activate it and authenticate
                $authMethod = $this->dispatcher->getParam("authMethod");
                if (!empty($authMethod)) {

                        $this->auth->activate($authMethod, 'web');
                        if ($this->auth->accepted()) {

                                $this->_registerUserSession($authMethod);
                        } else {

                                $this->auth->login();

                                // If it is formbased (ajax) authenticator and control 
                                // reaches to this point, it means authentication
                                // has been failed because of wrong username or
                                // password.
                                // In case of urlbased auth, control will never 
                                // reach here as in that case, it redirects to 
                                // login page .
                                // Disable view and send json response.
                                $this->view->disable();
                                return $this->response->setJsonContent(array(
                                            "status" => "failed"
                                ));
                        }
                } else {

                        // fetch the list of authentication methods from auth.php
                        $authMethods = $this->auth->getAuthChain("web");

                        // format data to be sent to view
                        $authMethodsData = array();
                        foreach ($authMethods as $authMethodCode => $authMethodObj) {
                                if ($authMethodObj->visible) {
                                        $authMethodsData[] = array(
                                                "code" => $authMethodCode,
                                                "name" => $authMethodObj->description,
                                                "type" => $authMethodObj->type
                                        );
                                }
                        }

                        // pick view and send data
                        $this->view->setVar("authMethods", $authMethodsData);
                        $this->view->pick("auth/authenticators");
                }
        }

        /**
         * Logs out the active session redirecting to the index
         *
         * @return unknown
         */
        public function logoutAction()
        {
                // get the method used to check authentication while user logged in
                $authData = $this->session->get('authenticated');

                // unset session data
                $this->session->destroy();

                // logout
                $this->auth->activate($authData['authenticator'], 'web')
                    ->logout();

                // send back message
                $this->flash->success('You have been successfully logged out.');
                return $this->response->redirect('/index');

                /* return $this->dispatcher->forward( array(
                  'controller' => 'index',
                  'action' => 'index'
                  )); */
        }

        /**
         * Register authenticated user into session
         * Authentication service will call this function.
         * 
         * @param User   $user The serialized user object
         * @param String $authMethod Name of authentication method used to login
         */
        private function _registerUserSession($authMethod)
        {		
                // store user data in session
                $this->session->start();
                $this->session->set('authenticated', array(
                        'user'          => $this->auth->getSubject(), 
                        'authenticator' => $authMethod
                ));

                // redirect to LOGIN_SUCCESS_URL
                return $this->response->redirect(self::LOGIN_SUCCESS_URL);
        }

}
