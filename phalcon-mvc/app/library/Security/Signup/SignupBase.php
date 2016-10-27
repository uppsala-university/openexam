<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
 * Description of SignupBase
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
