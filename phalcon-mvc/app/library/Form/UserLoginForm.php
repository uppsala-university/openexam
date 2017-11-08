<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
                $this->add(new Submit("fsubmit", array('name' => $login->fname, 'value' => 'Login', 'class' => 'btn btn-success')));
        }

}
