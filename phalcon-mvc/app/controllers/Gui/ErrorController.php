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
                        'contact' => $this->config->contact->toArray()
                ));
        }

        public function indexAction()
        {
                $this->checkAccess();
        }

        /**
         * Handler/action not found.
         */
        public function show404Action()
        {
                $this->checkAccess();
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Internal server error.
         */
        public function show500Action()
        {
                $this->checkAccess();
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Service not available error.
         */
        public function show503Action()
        {
                $this->checkAccess();
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

        /**
         * Generic error handler.
         */
        public function showErrorAction()
        {
                $this->checkAccess();
                $this->view->setVar('error', $this->dispatcher->getParam('error'));
        }

}
