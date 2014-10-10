<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    GuiController.php
// Created: 2014-08-27 11:35:20
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers;

/**
 * Base class for gui controllers.
 * 
 * The GuiController class serve as base class for all gui controllers
 * and helps to setup templates for views
 *  
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class GuiController extends \Phalcon\Mvc\Controller
{

        public function initialize()
        {
                $this->view->setLayout('main');
        }
        
}
