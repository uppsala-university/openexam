<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    ServiceController.php
// Created: 2014-08-25 00:15:47
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers;

use OpenExam\Library\WebService\Common\Exception as ServiceException;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;

/**
 * Base class for data service controllers.
 * 
 * The ServiceController class is the base for service controllers
 * providing SOAP, REST or AJAX response as opposite to producing
 * HTML output.
 * 
 * The deriving class should implement exceptionAction() to send error
 * response to client in service dependent encoding format.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ServiceController extends ControllerBase
{

        /**
         * The request payload.
         * @var array 
         */
        private $_payload;

        public function initialize()
        {
                parent::initialize();
                $this->view->disable();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_payload);
        }

        /**
         * Get service request.
         * @param callable $remapper Callback remapping request parameters (e.g. exams -> exam_id).
         * @return ServiceRequest
         */
        protected abstract function getRequest($remapper = null);

        /**
         * Send service response.
         * @param ServiceResponse $response The service response.
         */
        protected abstract function sendResponse($response);

        /**
         * Get input (model) data and params from request.
         * @return array
         * @throws ServiceException
         */
        protected function getPayload()
        {
                // 
                // Cache payload in this class.
                // 
                if (isset($this->_payload)) {
                        return $this->_payload;
                }

                // 
                // Payload is either on stdin or in POST/PUT-data. Watch out for
                // PHP's automatic conversion of whitespace to underscore when 
                // populating keys in $_POST data.
                // 
                if ($this->request->isPost() ||
                    $this->request->isPut()) {
                        $stdin = file_get_contents("php://input");
                }
                if ($stdin[0] != '{' && $stdin[0] != '[') {
                        $stdin = false;         // Only accept JSON
                }
                if ($stdin) {
                        $input = $stdin;
                } elseif ($this->request->isPost()) {
                        $input = $this->request->getPost();
                } elseif ($this->request->isPut()) {
                        $input = key($this->request->getPut());
                }

                // 
                // Data is encoded in array key:
                // 
                if (is_array($input) && count($input) == 1) {
                        if (empty(current($input)) && !empty(key($input))) {
                                $input = key($input);
                        }
                }

                // 
                // Convert data if needed/requested:
                // 
                if (is_string($input)) {
                        if ($this->request->getBestAccept() == 'application/json') {
                                $input = json_decode($input, true);
                        } elseif (($temp = json_decode($input, true)) != null) {
                                $input = $temp;
                        }
                        if (!isset($input)) {
                                throw new ServiceException("Unhandled content type");
                        }
                }

                // 
                // Currently, we are only handling array data;
                // 
                if (!is_array($input)) {
                        $input = (array) $input;
                }

                // 
                // Separate on model data and query params:
                // 
                foreach (array('data', 'params') as $part) {
                        if (isset($input[$part])) {
                                $$part = (array) $input[$part];
                        }
                }

                // 
                // Assume non-empty input is data by default:
                // 
                if (!isset($data) && !isset($params) && key($input) != "0") {
                        $data = $input;
                }
                if (!isset($data) && count($input) != 0) {
                        $data = $input;
                }
                if (!isset($data)) {
                        $data = array();
                }
                if (!isset($params)) {
                        $params = array();
                }

                // 
                // Cleanup if input data was missing.
                // 
                if (key($data) == "0") {
                        if ($data[0] == null) {
                                $data = array();
                        }
                        if (isset($data[0]) && is_string($data[0]) && strpbrk($data[0], '{[') != false) {
                                $data = array();
                        }
                }

                // 
                // Pack data and params in payload.
                // 
                return $this->_payload = array($data, $params);
        }

}
