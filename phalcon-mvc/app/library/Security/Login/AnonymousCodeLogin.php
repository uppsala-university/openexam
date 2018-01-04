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
// File:    AnonymousCodeLogin.php
// Created: 2016-11-18 01:15:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login;

use OpenExam\Library\Form\CodeLoginForm;
use OpenExam\Library\Security\Login\Base\FormLogin;
use OpenExam\Models\Student;
use Phalcon\Config;
use UUP\Authentication\Authenticator\RequestAuthenticator;
use UUP\Authentication\Validator\Validator;

/**
 * Login using anonymous code.
 * 
 * Use third argument with a unique name to support multiple anonymous code
 * databases. It's easy to extend to other database types along with what's 
 * outlined by this file. Perhaps connecting with an HTTP-server.
 * 
 * $auth = new AnonymousCodeLogin(
 *      $config, false, array(
 *              'form' => array(
 *                      'name' => 'myform',
 *                      'pass' => 'secret'
 *              )
 *      )
 * );
 * 
 * @property-read string $secret A shared secret.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AnonymousCodeLogin extends RequestAuthenticator implements FormLogin
{

        /**
         * The corresponding user principal.
         * @var string 
         */
        private $_stud;
        /**
         * The shared secret.
         * @var string 
         */
        private $_secret;

        /**
         * Constructor.
         * @param Config $config System configuration.
         * @param array $options Form options.
         */
        public function __construct($config, $secret = false, $options = array(
                'form' => array(
                        'login' => null,
                        'name'  => null,
                        'pass'  => null
                )
        ))
        {
                // 
                // These are the default options:
                // 
                $defaults = array(
                        'form' => array(
                                'name' => 'anon',
                                'pass' => 'code1',
                                'user' => 'exam1'
                        )
                );

                // 
                // Use secret.
                // 
                if ($secret) {
                        $this->_secret = $secret;
                } else {
                        $this->_secret = date('Ymd');
                }

                // 
                // Merge caller options with default. Using array_merge() is
                // not possible because of numeric keys.
                // 
                foreach ($defaults as $service => $array) {
                        if (!isset($options[$service])) {
                                $options[$service] = array();
                        }
                        foreach ($array as $key => $val) {
                                if (!isset($options[$service][$key]) || $options[$service][$key] == null) {
                                        $options[$service][$key] = $val;
                                }
                        }
                }

                // 
                // Relocate login URI to match form name:
                // 
                if (!isset($options['form']['login'])) {
                        $options['form']['login'] = $config->application->baseUri . 'auth/form/' . $options['form']['name'];
                }

                // 
                // It's tempting to implement the validator interface in this 
                // class and pass this as validator to parent. That will not work 
                // as PHP gets lost in referencing this.
                // 
                $validator = new AnonymousCodeValidator($this);

                // 
                // Call parent constructor and set visibility:
                // 
                parent::__construct($validator, $options['form']);
                parent::control(self::SUFFICIENT);
                parent::visible(true);
        }

        public function __get($name)
        {
                if ($name == 'secret') {
                        return $this->_secret;
                } else {
                        return parent::__get($name);
                }
        }

        /**
         * The form name or null.
         * @return string
         */
        public function form()
        {
                return $this->name;
        }

        /**
         * The password request parameter name.
         * @return string
         */
        public function pass()
        {
                return $this->pass;
        }

        /**
         * The username request parameter name.
         * @return string
         */
        public function user()
        {
                return $this->user;
        }

        /**
         * Return authenticated subject.
         * @return string
         */
        public function getSubject()
        {
                return $this->_stud;
        }

        public function setSubject($user)
        {
                $this->_stud = $user;
        }

        /**
         * Creates the anonyous code login form.
         * @return CodeLoginForm
         */
        public function create()
        {
                return new CodeLoginForm($this);
        }

}

/**
 * The anonymous code validator.
 * 
 * An helper class for AnonymousCodeLogin that is taking care of authentication
 * of students anonymous code. Calls owner class on successful authentication
 * to return the user principal.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AnonymousCodeValidator implements Validator
{

        /**
         * The login object.
         * @var AnonymousCodeLogin 
         */
        private $_login;
        /**
         * The username to validate.
         * @var string 
         */
        private $_user;
        /**
         * The password to validate.
         * @var string 
         */
        private $_pass;
        /**
         * The shared secret.
         * @var string 
         */
        private $_secret;

        /**
         * Constructor.
         * @param AnonymousCodeLogin $login The callback object.
         */
        public function __construct($login)
        {
                $this->_login = $login;
                $this->_secret = filter_input(INPUT_POST, 'secret');
        }

        /**
         * Authenticate current credentials.
         * @return boolean
         */
        public function authenticate()
        {
                // 
                // Check shared secret.
                // 
                if ($this->_secret != $this->_login->secret) {
                        return false;
                }

                // 
                // Find student by code and set user principal.
                // 
                if (($stud = Student::findFirst(array(
                            'conditions' => 'code = :code: AND exam_id = :exam:',
                            'bind'       => array(
                                    'code' => $this->_pass,
                                    'exam' => $this->_user
                        )))) != null) {
                        $this->_login->setSubject($stud->user);
                        return true;
                } else {
                        return false;
                }
        }

        /**
         * Set credentials for authentication.
         * @param string $user The username.
         * @param string $pass The password.
         */
        public function setCredentials($user, $pass)
        {
                $this->_user = $user;
                $this->_pass = $pass;
        }

}
