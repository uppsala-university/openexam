<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LoginSelect.php
// Created: 2015-02-10 09:47:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Form;

use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Form;
use UUP\Authentication\Stack\AuthenticatorChain;

/**
 * Select login method form.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class LoginSelect extends Form
{

        /**
         * Form initialize method.
         * @param AuthenticatorChain $authenticators
         */
        public function initialize($authenticators)
        {
                $options = array();
                foreach ($authenticators as $name => $plugin) {
                        if ($plugin->visible) {
                                $options[$name] = $plugin->get('desc');
                        }
                }

                $this->setAction($this->url->get('auth/login'));
                $this->add(new Select("auth", $options, array('class' => 'form-control', 'style' => 'max-width: 300px')));
                $this->add(new Hidden("embed", array("value" => $this->request->get("embed"))));
                $this->add(new Submit('submit', array('value' => 'Continue', 'class' => 'btn btn-success', 'style' => 'min-width: 80px')));
        }

}
