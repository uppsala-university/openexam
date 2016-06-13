<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceRequest.php
// Created: 2015-01-29 10:50:39
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Common;

/**
 * Generic representation of a web service request.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ServiceRequest
{
        /**
         * The request data.
         * @var array 
         */
        public $data;
        /**
         * The request parameters.
         * @var array 
         */
        public $params;

        /**
         * Constructor.
         * @param array $data
         * @param type $params
         */
        public function __construct($data, $params = array())
        {
                $this->data = $data;
                $this->params = $params;
        }

}
