<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PublicController.php
// Created: 2014-08-26 09:18:12
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;
use Phalcon\Mvc\View,
    Phalcon\Mvc\Controller;

/**
 * Public controller for rendering public (unauthorized) pages.
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class PublicController extends \OpenExam\Controllers\GuiController
{

        public function initialize()
        {
                parent::initialize();
        }

        public function indexAction()
        {
            $this->view->disableLevel(View::LEVEL_BEFORE_TEMPLATE);
            /* data to be sent */
        }

        public function aboutAction()
        {
            $this->view->disableLevel(View::LEVEL_BEFORE_TEMPLATE);
        }

        public function helpAction($role, $model, $action)
        {
            /* data to be sent */
        }

        public function contactusAction($role, $model, $action)
        {
            /* data to be sent */
        }

}
