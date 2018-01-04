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
// File:    UserLoginForm.php
// Created: 2015-02-09 12:04:29
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Form;

use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Form;
use UUP\Authentication\Authenticator\RequestAuthenticator;

/**
 * Basic login form.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UserLoginForm extends Form
{

        /**
         * Form initialize method.
         * @param RequestAuthenticator $login The selected authenticator.
         */
        public function initialize($login)
        {
                $this->setAction($this->url->get('auth/login/' . $login->name));

                $this->setUserOption('description', $login->description);
                $this->setUserOption('information', "Use your domain account for login. Contact the invigilator if you have any questions or problem with the login.<br><br>Example username: john1234@user.uu.se");

                $this->add(new Text("fuser", array('name' => $login->fuser, 'placeholder' => sprintf("login@%s", $this->config->user->domain), 'class' => 'form-control')));
                $this->add(new Password("fpass", array('name' => $login->fpass, 'placeholder' => 'Type your password', 'class' => 'form-control')));
                $this->add(new Hidden("fembed", array("value" => $this->request->get("embed"))));
                $this->add(new Submit('fcancel', array('id' => 'cancel', 'value' => 'Back', 'class' => 'btn btn-default', 'style' => 'min-width: 80px', 'onclick' => 'history.back(); return false;')));
                $this->add(new Submit("fsubmit", array('name' => $login->fname, 'value' => 'Login', 'class' => 'btn btn-success', 'style' => 'min-width: 80px')));
        }

}
