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
