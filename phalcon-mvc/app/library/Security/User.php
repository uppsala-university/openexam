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
 * unqualified usernames must be set.
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
        private $domain;
        /**
         * The user name.
         * @var string 
         */
        private $user;

        /**
         * Constructor.
         * @param string $user The username (simple or principal).
         * @param string $domain The user domain.
         */
        public function __construct($user = null, $domain = null)
        {
                if (isset($user)) {
                        if (isset($domain)) {
                                $this->user = $user;
                                $this->domain = $domain;
                        } elseif (isset($this->config['user']['domain'])) {
                                $this->user = $user;
                                $this->domain = $this->config['user']['domain'];
                        } else {
                                $this->user = $user;
                        }

                        if (($pos = strpos($this->user, '@'))) {
                                $this->domain = substr($this->user, $pos + 1);
                                $this->user = substr($this->user, 0, $pos);
                        }

                        if (!isset($this->domain)) {
                                throw new Exception(_("Missing domain part in username"));
                        }

                        if (isset($this->config['user']['roles'])) {
                                $this->roles = new Roles($this->config['user']['roles']);
                        } else {
                                $this->roles = new Roles();
                        }
                }
        }

        /**
         * Get user principal name.
         * @return string
         */
        public function getPrincipalName()
        {
                if (isset($this->user)) {
                        return sprintf("%s@%s", $this->user, $this->domain);
                }
        }

        /**
         * Get domain part of principal name.
         * @return string
         */
        public function getDomain()
        {
                return $this->domain;
        }

        /**
         * Get user part of principal name.
         * @return string
         */
        public function getUser()
        {
                return $this->user;
        }

}
