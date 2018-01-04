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
// File:    IndexController.php
// Created: 2014-08-26 09:18:12
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// Author:  Anders Lövgren (QNET)
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
                $this->checkAccess();
                $this->view->disableLevel(View::LEVEL_BEFORE_TEMPLATE);
        }

        public function aboutAction()
        {
                $this->checkAccess();
                $this->view->setTemplateBefore('cardbox');
        }

}
