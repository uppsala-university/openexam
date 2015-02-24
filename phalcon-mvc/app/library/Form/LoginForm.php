<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LoginForm.php
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
class LoginForm extends Form
{

        /**
         * Form initialize method.
         * @param RequestAuthenticator $login The selected authenticator.
         */
        public function initialize($login)
        {
                $this->setAction($this->url->get('auth/login/' . $login->name));
                $this->setUserOption('description', $login->description);
                $this->add(new Text('user', array('name' => $login->user)));
                $this->add(new Password('pass', array('name' => $login->pass)));
                $this->add(new Hidden("embed", array("value" => $this->request->get("embed"))));
                $this->add(new Submit('submit', array('name' => $login->name, 'value' => 'Login', 'class' => 'btn-submit')));
        }

}
