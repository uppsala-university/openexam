<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SoapController.php
// Created: 2015-03-13 00:18:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Service;

use Exception;
use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Monitor\Performance\Profiler;
use SoapServer;

/**
 * Common base class for SOAP controllers.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SoapController extends ServiceController
{

        protected function getRequest($remapper = null)
        {
                // Ignore
        }

        protected function sendResponse($response)
        {
                if ($this->profiler->enabled()) {
                        $this->response->setHeader(Profiler::HEADER, sprintf(
                                "%f:%f:%f", $this->request->getHeader(Profiler::HEADER), $this->profiler->initial(), microtime(true)
                        ));
                }
        }

        /**
         * The exception handler.
         * @param Exception $exception
         */
        public function exceptionAction($exception)
        {
                // 
                // TODO: Verify function using a SOAP client.
                // 
                $server = new SoapServer(null);
                $server->fault("Server", $exception->getMessage());
        }

}
