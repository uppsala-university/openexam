<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LoginTrait.php
// Created: 2014-09-10 15:50:09
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security\Login\Base;

use UUP\Authentication\Authenticator\Authenticator;

/**
 * Trait for login classes.
 * 
 * Decorates the login classes with the $type property.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class LoginTrait implements LoginHandler
{

        /**
         * Authenticator type (e.g formbased, urlbased)
         * @var type 
         */
        private $type;

        public function __get($name)
        {
                if ($name == 'type') {
                        return $this->type;
                } else {
                        return parent::__get($name);
                }
        }

        public function type($type)
        {
                $this->type = $type;
                return $this;
        }

        private function initialize($type, $desc, $name)
        {
                $this->type = $type;
                $this->control(Authenticator::sufficient);
                $this->visible(true);
                $this->description($desc);
                $this->name($name);
        }

}
