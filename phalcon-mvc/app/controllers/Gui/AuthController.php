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
        CONST LOGIN_SUCCESS_URL = "index";
        CONST LOGIN_FAILURE_URL = "";
        
        
        public function initialize()
        {
                // disable layout for the views
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
                
                parent::initialize();
        }

        /**
         * Action that is called after authentication process gets completed. 
         */
        public function loginAction()
        {
                
                // redirect if already logged in
                if($this->session->has('authenticated'))
                        return $this->response->redirect($loginSuccessUrl);
                
                // if authentication method is set, activate it and authenticate
                $authMethod = $this->dispatcher->getParam("authMethod");
                if(!empty($authMethod)) {

                        $this->auth->activate($authMethod, 'web');
                        if($this->auth->accepted()) {
                               
                               $this->_registerUserSession($authMethod);
                        } else {
                                
                                // return response of login() in json format if we get any.
                                // helpful for ajax based authentication.
                                $loginResponse = $this->auth->login();
                                if(!is_null($loginResponse)) {
                                        
                                        if($loginResponse === true) {
                                                
                                                $this->_registerUserSession($authMethod);
                                        } else {
                                                
                                                // Authentication failed. 
                                                // Disable view and send json response.
                                                $this->view->disable();
                                                $response = new \Phalcon\Http\Response();
                                                $response->setContent(json_encode(
                                                        array (
                                                                "validated" => "false"
                                                        )));
                                                return $response;
                                        }        
                                }
                                
                        }
                        
                } else {
                        // fetch the list of authentication methods from auth.php
                        $authMethods = $this->auth->getAuthChain("web");
                        
                        // format data to be sent to view
                        $authMethodsData = array();
                        foreach($authMethods as $authMethodCode => $authMethodObj) {
                                if($authMethodObj->visible) {
                                        $authMethodsData[] = array(
                                                "code"  => $authMethodCode,
                                                "name"  => $authMethodObj->description,
                                                "type"  => $authMethodObj->type
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
                
                /*return $this->dispatcher->forward( array(
                                'controller' => 'index',
                                'action' => 'index'
                        ));*/
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
                // prepare user object
                $userObj = $this->user->setUser($this->auth->getSubject());

                // store user data in session
                $this->session->set('authenticated', array(
                        'user'          => serialize($userObj),
                        'authenticator' => $authMethod
                ));

                // redirect to LOGIN_SUCCESS_URL
                return $this->response->redirect(self::LOGIN_SUCCESS_URL);
        }
        
}
