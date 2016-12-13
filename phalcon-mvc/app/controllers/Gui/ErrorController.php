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

                $this->view->setTemplateBefore('error');
                $this->view->setVars(array(
                        'icon'    => $this->url->get('img/cross-button.png'),
                        'style'   => $this->request->isAjax() ? "" : "margin-top: 50px",
                        'contact' => $this->config->contact->toArray()
                ));
        }

        public function indexAction()
        {
                
        }

        /**
         * Handler/action not found.
         */
        public function show404Action()
        {
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Internal server error.
         */
        public function show500Action()
        {
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Service not available error.
         */
        public function show503Action()
        {
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Generic error handler.
         */
        public function showErrorAction()
        {
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

}
