<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AjaxController.php
// Created: 2014-11-13 09:35:22
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service;

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\WebService\Common\Exception as ServiceException;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;

/**
 * Common base class for AJAX controllers.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class AjaxController extends ServiceController
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

        public function initialize()
        {
                set_exception_handler(array($this, 'exception_handler'));
                parent::initialize();
        }

        /**
         * The exception handler.
         * @param \Exception $exception
         */
        public function exception_handler($exception)
        {
                $this->report($exception);
                $this->sendResponse(new ServiceResponse(null, ServiceHandler::ERROR, $exception->getMessage()));
        }

        /**
         * Get service request.
         * @return ServiceRequest
         * @throws ServiceException
         */
        protected function getRequest($remapper = null)
        {
                if ($this->request->isPost()) {
                        list($data, $params) = $this->getPayload();
                        return new ServiceRequest($data, $params);
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
                $this->response->send();
        }

}
