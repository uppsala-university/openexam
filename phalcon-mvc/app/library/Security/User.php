<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    User.php
// Created: 2014-09-02 10:57:05
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

use Phalcon\Mvc\User\Component;

/**
 * Represents a logged on user.
 * 
 * This class supports user principal names. The default domain for
 * unqualified usernames must be set in system config. 
 * 
 * This class is intentional immutable to prevent priviledge escalation 
 * by changing the user associated with the roles by misstake.
 * 
 * The "act-as" pattern is supported by passing an array of roles to
 * the constructor or by setting the roles property. Use this feature 
 * with *caution* as it is effectivelly user impersonation.
 * 
 * @property Roles $roles The roles associated with this user.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class User extends Component
{

        /**
         * The user domain.
         * @var string 
         */
        private $_domain;
        /**
         * The user name.
         * @var string 
         */
        private $_user;

        /**
         * Constructor.
         * @param string $user The username (simple or principal).
         * @param string $domain The user domain.
         */
        public function __construct($user = null, $domain = null, $roles = array())
        {
                if (isset($user)) {
                        if (isset($domain)) {
                                $this->_user = $user;
                                $this->_domain = $domain;
                        } elseif ($this->config->user->domain != null) {
                                $this->_user = $user;
                                $this->_domain = $this->config->user->domain;
                        } else {
                                $this->_user = $user;
                        }

                        if (($pos = strpos($this->_user, '@'))) {
                                $this->_domain = substr($this->_user, $pos + 1);
                                $this->_user = substr($this->_user, 0, $pos);
                        }

                        if (!isset($this->_domain)) {
                                throw new Exception(_("Missing domain part in username"));
                        }

                        if (count($roles) != 0) {
                                $this->roles = new Roles($roles);
                        } elseif ($this->config->user->roles->count() != 0) {
                                $this->roles = new Roles($this->config->user->roles);
                        } else {
                                $this->roles = new Roles();
                        }
                }
        }

        public function __toString()
        {
                return isset($this->_user) ? $this->getPrincipalName() : "";
        }
        
        /**
         * Get user principal name.
         * @return string
         */
        public function getPrincipalName()
        {
                if (isset($this->_user)) {
                        return sprintf("%s@%s", $this->_user, $this->_domain);
                }
        }

        /**
         * Get domain part of principal name.
         * @return string
         */
        public function getDomain()
        {
                return $this->_domain;
        }

        /**
         * Get user part of principal name.
         * @return string
         */
        public function getUser()
        {
                return $this->_user;
        }

}
