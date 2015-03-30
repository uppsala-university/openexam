<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AccessController.php
// Created: 2014-12-16 17:20:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Ajax;

use OpenExam\Controllers\Service\AjaxController;
use OpenExam\Library\WebService\Handler\AccessHandler;

/**
 * Student exam access controller.
 * 
 * This controller can be called as student or invigilator through AJAX
 * to open/close (student) the exam or approve/release (invigilator) exam
 * locks.
 * 
 * Student actions (open/close exam):
 * --------------------------------------------------
 * /ajax/access/open    input: '{"exam_id":eid}'
 * /ajax/access/close   input: '{"exam_id":eid}'
 * 
 * Invigilator actions (approve/release exam lock):
 * --------------------------------------------------
 * /ajax/access/approve input: '{"exam_id":eid,"lock_id":lid}'
 * /ajax/access/release input: '{"exam_id":eid,"lock_id":lid}'
 * 
 * Example:
 * 
 * # As invigilator, release lock (id=912) on exam (id=367):
 * curl -XPOST /ajax/access/release -d '{"exam_id":367,"lock_id":912}'
 * 
 * # As student, try to gain access to exam 367:
 * curl -XPOST /ajax/access/open -d '{"exam_id":367}'
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessController extends AjaxController
{

        /**
         * @var AccessHandler 
         */
        protected $handler;

        public function initialize()
        {
                parent::initialize();
                $this->handler = new AccessHandler($this->getRequest(), $this->user);
                
        }

        /**
         * Open exam action.
         */
        public function openAction()
        {
                $response = $this->handler->open();
                $this->sendResponse($response);
        }

        /**
         * Close exam action.
         */
        public function closeAction()
        {
                $response = $this->handler->close();
                $this->sendResponse($response);
        }

        /**
         * Approve exam lock action.
         */
        public function approveAction()
        {
                $response = $this->handler->approve();
                $this->sendResponse($response);
        }

        /**
         * Release exam lock action.
         */
        public function releaseAction()
        {
                $response = $this->handler->release();
                $this->sendResponse($response);
        }

}
