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

namespace OpenExam\Library\Security\Authenticator;

use UUP\Authentication\Library\Authenticator\AuthenticatorBase;

/**
 * Login wrapper class
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
abstract class Login extends AuthenticatorBase
{
        /**
         * Authenticator type (e.g formbased, urlbased)
         * @var type 
         */
        private $type;
        
        /**
         * Login Authenticator
         * @var type 
         */
        private $loginAuthenticator;


        public function __get($propertyName)
        {
                if($propertyName == 'type') {
                        
                        return $this->type;
                } else if($propertyName == 'loginAuthenticator') {
                        
                        return $this->loginAuthenticator;
                } else {
                        
                        return $this->loginAuthenticator->$propertyName;
                }
                
        }
        
        /**
         * Sets authenticator type
         * @param type $value
         * @return \OpenExam\Library\Security\Authenticator\Login
         */
        public function type($value)
        {
                $this->type = (string) $value;
                return $this;
        }
        
        /**
         * Sets authenticator for login
         * @param type $authObj
         * @return \OpenExam\Library\Security\Authenticator\Login
         */
        public function loginAuthenticator($authObj)
        {
                $this->loginAuthenticator = $authObj;
                return $this;
        }


        /**
         * Sets the short name for wrapped authenticator.
         * @param string $text
         * @return Authenticator
         */
        public function name($text)
        {
                $this->loginAuthenticator->name($text);
                return $this;
        }

        /**
         * Sets a longer descriptive text for wrapped authenticator.
         * @param string $text
         * @return Authenticator
         */
        public function description($text)
        {
                $this->loginAuthenticator->description($text);
                return $this;
        }

        /**
         * Set this authenticator can be selected by remote user as an authentication method.
         * @param bool $bool
         * @return Authenticator
         */
        public function visible($value)
        {
                $this->loginAuthenticator->visible($value);
                return $this;
        }
        
}
