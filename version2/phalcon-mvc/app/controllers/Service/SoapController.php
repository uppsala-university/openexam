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

use OpenExam\Controllers\ServiceController;

/**
 * Description of SoapController
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
                // Ignore
        }

        /**
         * The exception handler.
         * @param \Exception $exception
         */
        public function exceptionAction($exception)
        {
                // 
                // TODO: Verify function using a SOAP client.
                // 
                $server = new \SoapServer(null);
                $server->fault("Server", $exception->getMessage());
        }

}
