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
         * The primary role.
         * @var string 
         */
        private $_role;

        /**
         * Constructor.
         * @param string $user The username (simple or principal).
         * @param string $domain The user domain.
         * @param string $role The primary role (for authorization).
         * @param array $roles Array of "act-as" roles.
         */
        public function __construct($user = null, $domain = null, $role = null, $roles = array())
        {
                $this->_role = $role;

                // @ToDO: move this sessions based user object initialzation to dispatcher
                if (!isset($user)) {
                        if ($this->session->has('authenticated')) {
                                $loggedIn = $this->session->get('authenticated');
                                $user = $loggedIn['user'];
                        }
                }

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

        /**
         * Get primary role.
         * @return string
         */
        public function getPrimaryRole()
        {
                return $this->_role;
        }

        /**
         * Set primary role.
         * @param string $role The primary role.
         * @return string The previous primary role.
         */
        public function setPrimaryRole($role)
        {
                $prev = $this->_role;
                $this->_role = $role;
                return $prev;
        }

        /**
         * Check if user has primary role.
         * @return bool
         */
        public function hasPrimaryRole()
        {
                return isset($this->_role);
        }

        /**
         * Aquire roles on object.
         * 
         * Try to aquire multiple roles at once. Returns the aquired roles or
         * false if none was aquired. If $id == 0, then roles are aquired 
         * globally.
         * 
         * <code>
         * // Get specific roles aquired:
         * $roles = $this->user->aquire(array('admin', 'teacher', 'student'), $id);
         * 
         * // Get all aquired roles:
         * $roles = $this->user->roles->getRoles($id);
         * </code>
         * 
         * @param array $roles The requested roles.
         * @param int $id The object ID.
         * @param mixed $default The default value if no role was aquired.
         * @return array
         * @see Roles::aquire()
         */
        public function aquire($roles, $id = 0, $default = false)
        {
                $aquired = array();

                foreach ($roles as $role) {
                        if ($this->roles->aquire($role, $id)) {
                                $aquired[] = $role;
                        }
                }

                return count($aquired) != 0 ? $aquired : $default;
        }

}
