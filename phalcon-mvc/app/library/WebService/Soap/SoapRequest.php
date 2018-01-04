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
// File:    SoapRequest.php
// Created: 2014-10-30 03:17:52
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap;

use Phalcon\Http\RequestInterface;

/**
 * SOAP request to handler class/object mapper.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SoapRequest
{

        /**
         * The SOAP action.
         * @var string 
         */
        private $_action;
        /**
         * The service location.
         * @var string 
         */
        private $_location;
        /**
         * The handler class.
         * @var string 
         */
        private $_handler;

        /**
         * Constructor.
         * @param RequestInterface $request The HTTP request.
         * @param string $action The SOAP action.
         * @param string $path The SOAP service path (e.g. /soap/core).
         */
        public function __construct($request, $action, $path)
        {
                $this->_action = $action;
                $this->_handler = sprintf("%s\Service\%sService", __NAMESPACE__, ucfirst($action));
                $this->_location = sprintf("%s://%s%s", $request->getScheme(), $request->getServerName(), $path);
        }

        /**
         * Get SOAP action.
         * @return string
         */
        public function getAction()
        {
                return $this->_action;
        }

        /**
         * Set SOAP action.
         * @param string $action The SOAP action (e.g. core).
         */
        public function setAction($action)
        {
                $this->_action = $action;
        }

        /**
         * Set handler class.
         * @param string $class The handler class.
         */
        public function setHandler($class)
        {
                $this->_handler = $class;
        }

        /**
         * Create SOAP service object.
         * @return SoapService
         */
        public function createService()
        {
                $service = new SoapService($this->_handler);
                $service->setLocation($this->_location);
                $service->setNamespace(sprintf("http://bmc.uu.se/soap/openexam/%s", $this->_action));
                return $service;
        }

}
