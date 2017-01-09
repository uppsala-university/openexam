<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SignupController.php
// Created: 2015-03-26 14:05:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Rest;

use OpenExam\Controllers\Service\RestController;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Library\WebService\Handler\SignupHandler;

/**
 * REST controller for user signup.
 * 
 * CRUD operations:
 * -------------------------
 * 
 * signup/              GET, PUT, POST, DELETE
 *   +-- teacher/       GET, PUT
 *          +-- id      PUT
 *          +-- id      PUT
 *   +-- student/       GET, PUT
 *          +-- id      PUT
 *          +-- id      PUT
 * 
 * Subscription:
 * -------------------------
 * 
 * /rest/signup (GET):    Read static signup information.
 * /rest/signup (PUT):    Update subscriptions.
 * /rest/signup (POST):   Insert teacher role (only for employees).
 * /rest/signup (DELETE): Remove teacher role (only for employees).
 * 
 * The subscription data is provided in the request payload or implicit
 * defined by the URL:
 * 
 * curl -XPUT ${BASEURL}/rest/signup -d '{"teacher":[465]}'     # Equivalent
 * curl -XPUT ${BASEURL}/rest/signup/teacher/465                # Equivalent
 * 
 * PUT on an containing URL will subscribe to all available exams:
 * 
 * curl -XPUT ${BASEURL}/rest/signup/teacher    # Subscribe to teacher exams.
 * curl -XPUT ${BASEURL}/rest/signup            # Subscribe to all exams.
 * 
 * Add/remove teacher role:
 * -------------------------
 * 
 * These operations can only be called by an employee. Whether being an
 * employee typical depends on the user affiliation that is queried from
 * external catalog services.
 * 
 * curl -XPOST   ${BASEURL}/rest/signup   # Insert teacher role for caller
 * curl -XDELETE ${BASEURL}/rest/signup   # Remove teacher role for caller
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SignupController extends RestController
{

        /**
         * @var SignupHandler 
         */
        protected $_handler;

        public function initialize()
        {
                parent::initialize();
                $this->_handler = new SignupHandler($this->getRequest(), $this->user, $this->config->signup);
        }
        
        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_handler);
                parent::__destruct();
        }

        public function indexAction()
        {
                switch ($this->request->getMethod()) {
                        case "GET":
                                $response = new ServiceResponse(
                                    $this->_handler, ServiceHandler::SUCCESS, $this->config->signup->toArray()
                                );
                                $this->sendResponse($response);
                                unset($response);
                                break;
                        case "PUT":
                                $response = $this->_handler->subscribe();
                                $this->sendResponse($response);
                                unset($response);
                                break;
                        case "POST":
                                $response = $this->_handler->insert();
                                $this->sendResponse($response);
                                unset($response);
                                break;
                        case "DELETE":
                                $response = $this->_handler->remove();
                                $this->sendResponse($response);
                                unset($response);
                                break;
                }
        }

        public function teacherAction()
        {
                switch ($this->request->getMethod()) {
                        case "GET":
                                $response = new ServiceResponse(
                                    $this->_handler, ServiceHandler::SUCCESS, $this->config->signup->teacher->toArray()
                                );
                                $this->sendResponse($response);
                                unset($response);
                                break;
                        case "PUT":
                                $response = $this->_handler->subscribe($this->getData());
                                $this->sendResponse($response);
                                unset($response);
                                break;
                }
        }

        public function studentAction()
        {
                switch ($this->request->getMethod()) {
                        case "GET":
                                $response = new ServiceResponse(
                                    $this->_handler, ServiceHandler::SUCCESS, $this->config->signup->student->toArray()
                                );
                                $this->sendResponse($response);
                                unset($response);
                                break;
                        case "PUT":
                                $response = $this->_handler->subscribe($this->getData());
                                $this->sendResponse($response);
                                unset($response);
                                break;
                }
        }

        /**
         * Get normalized subscription data from URL and request parameters.
         * @return array
         */
        private function getData()
        {
                $result = array();

                $action = $this->dispatcher->getActionName();
                $idname = $this->dispatcher->getParam(0);

                $data = $this->_handler->getRequest()->data;

                if (isset($idname)) {
                        // 
                        // signup/teacher/<id>
                        // 
                        $result = array(
                                $action => array($idname)
                        );
                } elseif (count($data) && !isset($data[$action])) {
                        // 
                        // signup/teacher -d '[<id>]'
                        // 
                        $result = array(
                                $action => $data
                        );
                } elseif (count($data)) {
                        // 
                        // signup/teacher -d '{"teacher":[<id>]}'
                        // 
                        $result = $data;
                } else {
                        // 
                        // signup/teacher
                        // 
                        $result = array(
                                $action => true
                        );
                }
                
                return $result;
        }

}
