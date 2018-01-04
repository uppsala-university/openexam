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
 * Location/access information:
 * --------------------------------------------------
 * Takes an optional exam ID. The filter params can be passed to restrict
 * what information gets returned. Pass flat to collapse the array tree
 * structure of returned data.
 * 
 * /ajax/access/entries input: '{"exam_id":eid}'
 * /ajax/access/entries input: '{"data":{"exam_id":eid},"params":{"filter":{"system":true,"recent":false,"active":true}}}'
 * /ajax/access/entries input: '{"params":{"filter":{"system":true}}}'
 * /ajax/access/entries input: '{"params":{"filter":{"system":true},"flat":true}}'
 * 
 * It's also possible to pass the filter option (active, recent or system) 
 * through the URL. These two examples yields the same result:
 * 
 * /ajax/access/entries/active  input: '{"exam_id":eid}'
 * /ajax/access/entries/        input: '{"data":{"exam_id":eid},"params":{"filter":{"active":true}}}'
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessController extends AjaxController
{

        /**
         * @var AccessHandler 
         */
        protected $_handler;

        /**
         * Constructor.
         */
        public function __construct()
        {
                parent::__construct();
                $this->_handler = new AccessHandler($this->getRequest(), $this->user);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_handler);
                parent::__destruct();
        }

        /**
         * Open exam action.
         */
        public function openAction()
        {
                $response = $this->_handler->open();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Close exam action.
         */
        public function closeAction()
        {
                $response = $this->_handler->close();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Approve exam lock action.
         */
        public function approveAction()
        {
                $response = $this->_handler->approve();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Release exam lock action.
         */
        public function releaseAction()
        {
                $response = $this->_handler->release();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Location and access information action.
         */
        public function entriesAction()
        {
                if (($section = $this->dispatcher->getParam(0))) {
                        $response = $this->_handler->entries($this->location, $section);
                        $this->sendResponse($response);
                        unset($response);
                } else {
                        $response = $this->_handler->entries($this->location);
                        $this->sendResponse($response);
                        unset($response);
                }
        }

}
