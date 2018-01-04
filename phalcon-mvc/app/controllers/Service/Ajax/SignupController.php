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
// File:    SignupController.php
// Created: 2015-03-24 13:32:12
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Ajax;

use OpenExam\Controllers\Service\AjaxController;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Library\WebService\Handler\SignupHandler;

/**
 * AJAX controller for user signup.
 * 
 * Discover actions:
 * --------------------------
 * 
 * # Get exams that can be subscribed (as employee and student):
 * /ajax/signup
 * 
 * Signup actions:
 * --------------------------
 * 
 * /ajax/signup/insert          // Signup caller
 * /ajax/signup/remove          // Remove caller
 * 
 * Subscribe actions:
 * --------------------------
 * 
 * /ajax/signup/subscribe       input: '{"teacher":[...],"student":[...]}
 * 
 * Alias actions:
 * --------------------------
 * 
 * /ajax/signup/teacher         input: '{"id":eid}'
 * /ajax/signup/student         input: '{"id":eid}'
 * 
 * Examples:
 * --------------------------
 * 
 * # Get all exams that can subscribed to:
 * curl -XPOST ${BASEURL}/ajax/signup
 * 
 * # Subscribe to these exams:
 * curl -XPOST ${BASEURL}/ajax/signup/subscribe -d '{"teacher":[465,566,294],"student":[243,495,888]}'
 * 
 * # Subscribe to all teacher exams:
 * curl -XPOST ${BASEURL}/ajax/signup/subscribe -d '{"teacher":true}'
 * 
 * # Subscribe to all teacher and student exams:
 * curl -XPOST ${BASEURL}/ajax/signup/subscribe
 * 
 * # Add caller as teacher in system (only for employees):
 * curl -XPOST ${BASEURL}/ajax/signup/insert
 * 
 * # Remove caller as teacher from system (only for employees):
 * curl -XPOST ${BASEURL}/ajax/signup/remove
 * 
 * # These two are equivalent (using alias function in second example):
 * curl -XPOST ${BASEURL}/ajax/signup/subscribe -d '{"teacher":[465]}'
 * curl -XPOST ${BASEURL}/ajax/signup/teacher -d '{"id":465}'
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SignupController extends AjaxController
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

        /**
         * Discover subscribe options action.
         */
        public function indexAction()
        {
                $response = new ServiceResponse($this->_handler, ServiceHandler::SUCCESS, $this->config->signup->toArray());
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Insert teacher role action.
         */
        public function insertAction()
        {
                $response = $this->_handler->insert();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Remove teacher role action.
         */
        public function removeAction()
        {
                $response = $this->_handler->remove();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Subscribe to exams action.
         */
        public function subscribeAction()
        {
                $response = $this->_handler->subscribe();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Signup as student action.
         */
        public function studentAction()
        {
                $this->aliasAction('student');
        }

        /**
         * Signup as teacher action.
         */
        public function teacherAction()
        {
                $this->aliasAction('teacher');
        }

        private function aliasAction($name)
        {
                $request = $this->_handler->getRequest();
                $response = $this->_handler->subscribe(array(
                        $name => array(
                                $request->data['id']
                        )
                ));
                
                $response->result = $response->result[$name][$request->data['id']];
                $this->sendResponse($response);
                
                unset($response);
                unset($request);
        }

}
