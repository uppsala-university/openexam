<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    IndexController.php
// Created: 2014-08-26 09:18:12
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use Phalcon\Mvc\View;

/**
 * The index controller.
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class IndexController extends GuiController
{

        public function initialize()
        {
                parent::initialize();
        }

        public function indexAction()
        {
                $this->view->disableLevel(View::LEVEL_BEFORE_TEMPLATE);
        }

        public function aboutAction()
        {
                $this->view->setTemplateBefore('cardbox');
        }

}
