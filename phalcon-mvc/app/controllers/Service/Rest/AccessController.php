<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AccessController.php
// Created: 2015-01-26 16:31:18
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service\Rest;

use OpenExam\Controllers\Service\RestController;
use OpenExam\Library\WebService\Handler\AccessHandler;

/**
 * Student exam access controller.
 * 
 * This controller can be called as student or invigilator through REST
 * to open/close (student) the exam or approve/release (invigilator) exam
 * locks.
 * 
 * Student actions (open/close exam):
 * --------------------------------------------------
 * curl -XPOST ${BASEURL}/rest/access/open  -d '{"exam_id":243}'   // open exam 243
 * curl -XPOST ${BASEURL}/rest/access/close -d '{"exam_id":243}'   // close exam 243
 * 
 * Invigilator actions (approve/release exam lock):
 * --------------------------------------------------
 * curl -XPOST ${BASEURL}/rest/access/approve -d '{"lock_id":961}'   // approve lock 961
 * curl -XPOST ${BASEURL}/rest/access/release -d '{"lock_id":961}'   // release lock 961
 * 
 * Location/access information:
 * --------------------------------------------------
 * 
 * # The exam ID is passed through URL path:
 * curl -XGET ${BASEURL}/rest/access/entries/exam/829  // All sections
 * 
 * # Sections and output format can be defined:
 * curl -XGET ${BASEURL}/rest/access/entries/exam/829?section=active&flat=0
 * 
 * # Get system (pre-defined) locations in flat format:
 * curl -XGET ${BASEURL}/rest/access/entries/?section=system&flat=1"
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessController extends RestController
{

        /**
         * @var AccessHandler 
         */
        protected $_handler;

        public function initialize()
        {
                parent::initialize();

                $request = $this->getRequest(function($arg) {
                        if (!is_numeric($arg)) {
                                return "${arg}_id";
                        } else {
                                return $arg;
                        }
                });
                $this->_handler = new AccessHandler($request, $this->user);
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
                if (($section = $this->request->getQuery('section'))) {
                        $response = $this->_handler->entries($this->location, $section);
                        $this->sendResponse($response);
                        unset($response);
                        unset($section);
                } else {
                        $response = $this->_handler->entries($this->location);
                        $this->sendResponse($response);
                        unset($response);
                }
        }

}
