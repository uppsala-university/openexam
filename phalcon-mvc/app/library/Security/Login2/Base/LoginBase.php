<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Login.php
// Created: 2014-09-09 10:52:02
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

use UUP\Authentication\Authenticator\Authenticator;
use UUP\Authentication\Library\Authenticator\AuthenticatorBase;

/**
 * Base for login classes.
 * 
 * @property-read AuthenticatorBase $authenticator Get wrapped authenticator.
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
abstract class LoginBase
{

        const remote = 'remote';
        const form = 'form';

        /**
         * Authenticator type (e.g formbased, urlbased)
         * @var string 
         */
        private $type;
        /**
         * Login Authenticator
         * @var AuthenticatorBase 
         */
        private $authenticator;

        public function __construct($type, $authenticator)
        {
                $this->type = $type;
                $this->authenticator = $authenticator;
        }

        public function __get($name)
        {
                if (property_exists($this, $name)) {
                        return $this->$name;
                } else {
                        return $this->authenticator->name;
                }
        }

        /**
         * Sets authenticator type.
         * @param type $value
         * @return \OpenExam\Library\Security\Login\Login
         */
        public function type($value)
        {
                $this->type = (string) $value;
                return $this;
        }

        /**
         * Sets the short name for wrapped authenticator.
         * @param string $text
         * @return Authenticator
         */
        public function name($text)
        {
                $this->authenticator->name($text);
                return $this;
        }

        /**
         * Sets a longer descriptive text for wrapped authenticator.
         * @param string $text
         * @return Authenticator
         */
        public function description($text)
        {
                $this->authenticator->description($text);
                return $this;
        }

        /**
         * Set this authenticator can be selected by remote user as an authentication method.
         * @param bool $bool
         * @return Authenticator
         */
        public function visible($value)
        {
                $this->authenticator->visible($value);
                return $this;
        }

}
