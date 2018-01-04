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
// File:    AjaxController.php
// Created: 2014-11-13 09:35:22
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service;

use Exception;
use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Monitor\Performance\Profiler;
use OpenExam\Library\WebService\Common\Exception as ServiceException;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;

/**
 * Common base class for AJAX controllers.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AjaxController extends ServiceController
{

        /**
         * Success level response tag.
         */
        const SUCCESS = 'success';
        /**
         * Notice level response tag.
         */
        const NOTICE = 'notice';
        /**
         * Warning level response tag.
         */
        const WARNING = 'warning';
        /**
         * Failure level response tag.
         */
        const FAILURE = 'failed';

        /**
         * The exception handler.
         * @param Exception $exception
         */
        public function exceptionAction($exception)
        {
                $response = new ServiceResponse(null, ServiceHandler::ERROR, $exception->getMessage());

                $this->report($exception);
                $this->sendResponse($response);

                unset($response);
        }

        /**
         * Get service request.
         * @param callable $remapper Callback remapping path element to field names (e.g. exams -> exam_id).
         * @return ServiceRequest
         * @throws ServiceException
         */
        protected function getRequest($remapper = null)
        {
                if ($this->request->isPost()) {
                        list($data, $params) = $this->getPayload();
                        $request = new ServiceRequest($data, $params);
                        return $request;
                } else {
                        throw new ServiceException("Expected POST request");
                }
        }

        /**
         * Send service response.
         * @param ServiceResponse $response The service response.
         */
        protected function sendResponse($response)
        {
                $action = $this->dispatcher->getActionName();
                $target = $this->dispatcher->getControllerName();

                if ($response->status == ServiceHandler::SUCCESS) {
                        $status = self::SUCCESS;
                } elseif ($response->status == ServiceHandler::PENDING) {
                        $status = self::NOTICE;
                } else {
                        $status = self::FAILURE;
                }

                $this->response->setJsonContent(array(
                        $status => array(
                                'target' => $target,
                                'action' => $action,
                                'return' => $response->result
                )));

                if ($this->profiler->enabled()) {
                        $this->response->setHeader(Profiler::HEADER, sprintf(
                                "%f:%f:%f", $this->request->getHeader(Profiler::HEADER), $this->profiler->initial(), microtime(true)
                        ));
                }

                $this->response->send();
        }

}
