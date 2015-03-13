<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
use Phalcon\Mvc\Controller;

/**
 * Base class for data service controllers.
 * 
 * The ServiceController class is the base for service controllers
 * providing SOAP, REST or AJAX response as opposite to producing
 * HTML output.
 * 
 * Error and exception handling:
 * ------------------------------
 * 
 * The deriving class should install an exception handler that logs the
 * exception (thru report()) and forward a suitable message to peer.
 * 
 * // In the initialize() method of deriving class:
 * set_exception_handler(array($this, 'exception_handler'));
 * 
 * This class uses set_error_handler() to transform all triggered errors 
 * with sufficient high severity into exception that deriving class is
 * trapping and reporting thru its exception handler. The throwed exception
 * has type ErrorException
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ServiceController extends Controller
{

        /**
         * The request payload.
         * @var array 
         */
        private $payload;

        public function initialize()
        {
                $errormask = (E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR);

                $this->view->disable();
                set_error_handler(array($this, 'error_handler'), $errormask);
        }

        /**
         * Log error and throw exception.
         * @param int $code The error level (severity).
         * @param string $message The error message.
         * @param string $file The error file.
         * @param string $line The error line.
         * @throws \ErrorException
         */
        public function error_handler($code, $message, $file, $line)
        {
                // 
                // Log triggered error:
                // 
                $this->logger->system->log($code, sprintf("%s in %s on line %d", $message, $file, $line, $code));

                // 
                // Throw exception for errors above threshold:
                // 
                throw new \ErrorException($message, 500, $code, $file, $line);
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
         * Exception handler action.
         */
        public abstract function exceptionAction($exception);

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
                if (isset($this->payload)) {
                        return $this->payload;
                }

                // 
                // Payload is either on stdin or in POST/PUT-data:
                // 
                if ($this->request->isPost()) {
                        $input = $this->request->getPost();
                }
                if ($this->request->isPut()) {
                        $input = key($this->request->getPut());
                }
                if (isset($input) && $input == false) {
                        $input = file_get_contents("php://input");
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

//                if (!isset($input)) {
//                        throw new ServiceException("Input data is missing");
//                }

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
                                unset($input[$part]);
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

                $this->payload = array($data, $params);
                return $this->payload;
        }

        /**
         * Report service exception.
         * @param \Exception $exception The exception to report.
         * @param ServiceRequest $request The REST request object.
         */
        protected function report($exception)
        {
                $this->logger->system->begin();
                $this->logger->system->error(print_r(array(
                        'Exception' => array(
                                'Type'    => get_class($exception),
                                'Message' => $exception->getMessage(),
                                'File'    => $exception->getFile(),
                                'Line'    => $exception->getLine(),
                                'Code'    => $exception->getCode()
                        ),
                        'Request'   => array(
                                'Server'  => sprintf("%s (%s)", $this->request->getServerName(), $this->request->getServerAddress()),
                                'Method'  => $this->request->getMethod(),
                                'Payload' => $this->payload,
                                'Query'   => print_r($this->request->get(), true)
                        ),
                        'Source'    => array(
                                'User'   => $this->user->getPrincipalName(),
                                'Role'   => $this->user->getPrimaryRole(),
                                'Remote' => $this->request->getClientAddress(true)
                        )
                        ), true));
                $this->logger->system->commit();
        }

}
