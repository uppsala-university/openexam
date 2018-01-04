<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    UserLoginForm.php
// Created: 2016-11-18 02:47:44
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Form;

use OpenExam\Models\Exam;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Form;
use UUP\Authentication\Authenticator\RequestAuthenticator;

/**
 * Anonymous code login form.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CodeLoginForm extends Form
{

        /**
         * Form initialize method.
         * @param RequestAuthenticator $login The selected authenticator.
         */
        public function initialize($login)
        {
                // 
                // Get today exams:
                // 
                $exams = Exam::find(sprintf("DATE(starttime) = '%s'", date('Y-m-d')));

                $this->setAction($this->url->get('auth/login/' . $login->name));

                $this->setUserOption('description', $login->description);
                $this->setUserOption('information', "Select your exam and use your anonymous code as login. Contact the invigilator if you don't know the code.<br><br>Example code: AB-39845");

                $this->add(new Password('fpass', array('name' => $login->fpass, 'placeholder' => 'The anonymous code', 'class' => 'form-control')));
                $this->add(new Select('fexam', $exams, array('using' => array('id', 'name'), 'name' => $login->fuser, 'class' => 'form-control')));
                $this->add(new Hidden('fcode', array('name' => 'secret', 'value' => $login->secret)));
                $this->add(new Hidden("fembed", array('value' => $this->request->get("embed"))));
                $this->add(new Submit('fcancel', array('id' => 'cancel', 'value' => 'Back', 'class' => 'btn btn-default', 'style' => 'min-width: 80px', 'onclick' => 'history.back(); return false;')));
                $this->add(new Submit('fsubmit', array('name' => $login->fname, 'value' => 'Login', 'class' => 'btn btn-success', 'style' => 'min-width: 80px')));
        }

}
