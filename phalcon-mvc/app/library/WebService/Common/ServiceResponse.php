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
// File:    ServiceResponse.php
// Created: 2015-01-29 10:50:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Common;

/**
 * Generic representation of a web service response.
 * 
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ServiceResponse
{

        /**
         * The request handler.
         * @var ServiceHandler 
         */
        public $handler;
        /**
         * The HTTP status code.
         * @var int
         */
        public $status;
        /**
         * The request result.
         * @var mixed 
         */
        public $result;

        /**
         * Constructor.
         * @param ServiceHandler $handler The request handler.
         * @param int $status The HTTP status code.
         * @param mixed $result The request result.
         */
        public function __construct($handler, $status, $result)
        {
                $this->handler = $handler;
                $this->status = $status;
                $this->result = $result;
        }

}
