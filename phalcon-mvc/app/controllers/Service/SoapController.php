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
