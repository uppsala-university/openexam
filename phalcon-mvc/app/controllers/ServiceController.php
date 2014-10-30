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

use OpenExam\Library\Security\Capabilities;

/**
 * Base class for data service controllers.
 * 
 * The ServiceController class is the base for service controllers
 * providing SOAP, REST or AJAX response as opposite to producing
 * HTML output.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ServiceController extends \Phalcon\Mvc\Controller
{

        /**
         * The capabilities mapper.
         * @var Capabilities 
         */
        protected $capabilities;

        public function initialize()
        {
                $this->view->disable();
                $this->capabilities = new Capabilities(require(CONFIG_DIR . '/access.def'));
        }

}
