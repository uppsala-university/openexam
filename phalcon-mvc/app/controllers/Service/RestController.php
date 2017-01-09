<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RestController.php
// Created: 2015-01-26 16:25:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service;

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;

/**
 * Common base class for REST controllers.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RestController extends ServiceController
{

        /**
         * The exception handler.
         * @param \Exception $exception
         */
        public function exceptionAction($exception)
        {
                $this->report($exception);

                if ($exception instanceof SecurityException) {
                        $this->response->setStatusCode(ServiceHandler::FORBIDDEN, null);
                        $this->response->setJsonContent($exception->getMessage());
                        $this->response->send();
                } elseif ($exception->getCode() != 0) {
                        $this->response->setStatusCode($exception->getCode(), null);
                        $this->response->setJsonContent($exception->getMessage());
                        $this->response->send();
                } else {
                        $this->response->setStatusCode(ServiceHandler::ERROR, null);
                        $this->response->setJsonContent($exception->getMessage());
                        $this->response->send();
                }
        }

        /**
         * Handle service response.
         * @param ServiceResponse $response
         */
        protected function sendResponse($response)
        {
                $this->response->setStatusCode($response->status, null);
                $this->response->setJsonContent($response->result);
                
                if ($this->profiler->enabled()) {
                        $this->response->setHeader(Profiler::HEADER, sprintf(
                                "%f:%f:%f", $this->request->getHeader(Profiler::HEADER), $this->profiler->initial(), microtime(true)
                        ));
                }
                
                $this->response->send();
        }

        /**
         * Get service request.
         * @param callable $remapper Callback remapping path element to field names (e.g. exams -> exam_id).
         * @return ServiceRequest
         */
        protected function getRequest($remapper = null)
        {
                // 
                // Get REST request payload for POST and PUT:
                // 
                if ($this->request->isPost() || $this->request->isPut()) {
                        list($data, $params) = $this->getPayload();
                } else {
                        list($data, $params) = array(array(), array());
                }

                // 
                // Transform path into request data:
                // 
                foreach ($this->dispatcher->getParams() as $index => $curr) {
                        if (isset($remapper)) {
                                $curr = $remapper($curr);
                        }
                        if ($index % 2 == 0) {
                                $data[$curr] = false;
                        } else {
                                $data[$prev] = $curr;
                        }
                        $prev = $curr;
                }
                
                // 
                // Merge query parameters into params:
                // 
                $params = array_merge($params, $this->request->getQuery());

                // 
                // Create and return service request object:
                // 
                return new ServiceRequest($data, $params);
        }

}
