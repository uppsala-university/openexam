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
// File:    SignupBase.php
// Created: 2015-03-13 17:31:38
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Signup;

use OpenExam\Library\Security\Signup;
use Phalcon\Mvc\User\Component;

/**
 * Base class for signup.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class SignupBase extends Component implements Signup
{

        /**
         * Signup of teacher is enabled in config.
         * @var boolean 
         */
        protected $_enabled;
        /**
         * The exams available for assignment.
         * @var array 
         */
        protected $_exams;
        /**
         * The user to register and assign exams.
         * @var string 
         */
        protected $_caller;

        /**
         * Constructor.
         * @param string $user The affected user principal name.
         */
        public function __construct($user = null)
        {
                if (!isset($user)) {
                        $this->_caller = $this->user->getPrincipalName();
                } else {
                        $this->_caller = $user;
                }
        }

        /**
         * Set target user for all operations.
         * @param string $user The user principal name.
         */
        public function setUser($user)
        {
                $this->_caller = $user;
        }

        /**
         * Return true if teacher signup is enabled in config.
         * @return boolean
         */
        public function isEnabled()
        {
                return $this->_enabled;
        }

        /**
         * Get all exams available for assignment.
         * @return array
         */
        public function getExams()
        {
                return $this->_exams;
        }

}
