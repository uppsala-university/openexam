<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ErrorController.php
// Created: 2015-02-23 14:09:55
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;

/**
 * Handle error conditions.
 * 
 * Used for displaying nice error messages upon encountering some error 
 * condition. Error logging should already been handled when this controller
 * get dispatched.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ErrorController extends GuiController
{

        public function initialize()
        {
                parent::initialize();
                
                $this->view->setvar('icon', $this->url->get('img/cross-button.png'));
                $this->view->setVar('style', $this->request->isAjax() == false ? "margin-top: 50px" : "");
        }

        public function indexAction()
        {
                
        }

        /**
         * Handler/action not found.
         */
        public function show404Action()
        {
                $exception = $this->dispatcher->getParam('exception');
                $this->view->setVar('exception', $exception);
        }

        /**
         * Service not available error.
         */
        public function show503Action()
        {
                $exception = $this->dispatcher->getParam('exception');
                $this->view->setVar('exception', $exception);
        }

        public function showErrorAction()
        {
                $exception = $this->dispatcher->getParam('exception');
                $this->view->setVar('error', new Error($exception));
        }

}
