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
// File:    ControllerBase.php
// Created: 2015-03-13 02:30:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers;

use ErrorException;
use Phalcon\Mvc\Controller;

/**
 * Common base class for controllers.
 * 
 * This class uses set_error_handler() to transform all triggered errors 
 * with sufficient high severity into exception that deriving class is
 * trapping and reporting thru its exception handler. The throwed exception
 * has type ErrorException
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
abstract class ControllerBase extends Controller
{

        protected function initialize()
        {
                // 
                // The errors handled:
                // 
                $errormask = (E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR);
                
                // 
                // Install error and exception handlers:
                // 
                set_error_handler(array($this, 'error_handler'), $errormask);
                set_exception_handler(array($this, 'exceptionAction'));

                // 
                // Add profiling check point:
                // 
                $this->profiler->add("controller");

                // 
                // Force use of locale service:
                // 
                $this->locale->detect(null);
        }

        /**
         * Exception handler action.
         */
        public abstract function exceptionAction($exception);

        /**
         * Log error and throw exception.
         * @param int $code The error level (severity).
         * @param string $message The error message.
         * @param string $file The error file.
         * @param string $line The error line.
         * @throws ErrorException
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
                throw new ErrorException($message, 500, $code, $file, $line);
        }

        /**
         * Report service exception.
         * @param \Exception $exception The exception to report.
         */
        protected function report($exception)
        {
                try {
                        $session = $this->session->getId();
                        $request = $this->request->get();
                        $payload = method_exists($this, 'getPayload') ? $this->getPayload() : array();
                } catch (\Exception $exception) {
                        if (!isset($request)) {
                                $request = array('failed' => true);
                        }
                        if (!isset($payload)) {
                                $payload = array('failed' => true);
                        }
                }

                $this->logger->system->begin();
                $this->logger->system->error(print_r(array(
                        'Report'    => array(
                                'Logger' => __METHOD__,
                                'Trace'  => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]
                        ),
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
                                'Query'   => (array) $request,
                                'Payload' => (array) $payload,
                                'Session' => (string) $session
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
