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

use OpenExam\Library\Catalog\Affiliation;
use OpenExam\Library\Core\Settings;
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
 * with *caution* as it is effectivelly user impersonation. True user
 * impersonation is supported by the impersonate() method.
 * 
 * The affiliation is working on the effective user, not the real user. 
 * This means that if impersonation is active, then affiliation is done
 * using the impersonated user, not the actor (real user).
 * 
 * @property Roles $roles The roles associated with this user.
 * @property Settings $settings The user settings.
 * @property-read Impersonation $impersonation The current impersonation.
 * @property-read Affiliation $affiliation The user affiliation.
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
         * The primary group.
         * @var string 
         */
        private $_group;
        /**
         * Construction time injected roles.
         * @var array 
         */
        private $_injected;

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
                $this->_injected = $roles;

                if (isset($user)) {
                        if ($this->config->user->normalizer) {
                                $user = call_user_func($this->config->user->normalizer, $user);
                        }

                        if (isset($domain)) {
                                $this->_user = $user;
                                $this->_domain = $domain;
                        } elseif ($this->config->user->domain != null) {
                                $this->_user = $user;
                                $this->_domain = $this->config->user->domain;
                        } else {
                                $this->_user = $user;
                        }

                        if (strpos($this->_user, '@')) {
                                $this->setPrincipalName($this->_user);
                        }

                        if (!isset($this->_domain)) {
                                throw new Exception("Missing domain part in username $user");
                        }

                        $this->impersonation = new Impersonation();
                        if ($this->impersonation->active) {
                                $this->setPrincipalName($this->impersonation->impersonated);
                        }
                }
        }

        public function __get($property)
        {
                switch ($property) {
                        case 'roles':
                                return $this->getRoles();
                        case 'affiliation':
                                return $this->getAffiliation();
                        case 'settings':
                                return $this->getSettings();
                        default:
                                return parent::__get($property);
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
         * Set user principal name.
         * @param string $user The user principal string.
         */
        private function setPrincipalName($user)
        {
                if (($pos = strpos($user, '@'))) {
                        $this->_domain = substr($user, $pos + 1);
                        $this->_user = substr($user, 0, $pos);
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
         * Acquire roles on object.
         * 
         * Try to acquire multiple roles at once. Returns the acquired roles or
         * false if none was acquired. If $id == 0, then roles are acquired 
         * globally.
         * 
         * <code>
         * // Get specific roles acquired:
         * $roles = $this->user->acquire(array('admin', 'teacher', 'student'), $id);
         * 
         * // Get all acquired roles:
         * $roles = $this->user->roles->getRoles($id);
         * </code>
         * 
         * @param array $roles The requested roles.
         * @param int $id The object ID.
         * @param mixed $default The default value if no role was acquired.
         * @return array
         * @see Roles::acquire()
         */
        public function acquire($roles, $id = 0, $default = false)
        {
                $acquired = array();

                foreach ($roles as $role) {
                        if ($this->roles->acquire($role, $id)) {
                                $acquired[] = $role;
                        }
                }

                return count($acquired) != 0 ? $acquired : $default;
        }

        /**
         * Enable impersonation as $user.
         * 
         * If user is set, then impersonation is enabled as user if caller
         * is admin. The current username (in this object) is replaced with
         * the user argument.
         * 
         * If user is unset, then the current impersonation is breaked and
         * original user (from actor) is restored as current username in this
         * user object.
         * 
         * @param string $user The user to impersonate.
         * @return boolean 
         */
        public function impersonate($user)
        {
                if (isset($user) && !empty($user)) {
                        if (!$this->impersonation->enable($user)) {
                                return false;
                        } elseif (!(strpos($user, '@'))) {
                                return false;
                        } else {
                                $this->setPrincipalName($user);
                                $this->roles = new Roles();
                                return true;
                        }
                } else {
                        if (!$this->impersonation->active) {
                                return false;
                        } else {
                                $this->setPrincipalName($this->impersonation->actor);
                                $this->impersonation->disable();
                                return true;
                        }
                }
        }

        /**
         * Get roles object.
         * 
         * @return Roles
         */
        private function getRoles()
        {
                if (isset($this->_user)) {
                        if (count($this->_injected) != 0) {
                                return $this->roles = new Roles($this->_injected);
                        } elseif ($this->config->user->roles->count() != 0) {
                                return $this->roles = new Roles($this->config->user->roles);
                        } else {
                                return $this->roles = new Roles();
                        }
                }
        }

        /**
         * Get user affiliation.
         * @return Affiliation
         */
        private function getAffiliation()
        {
                if (isset($this->_user)) {
                        return $this->affiliation = new Affiliation($this->getPrincipalName());
                }
        }

        /**
         * Get user settings.
         * @return Settings
         */
        private function getSettings()
        {
                if (isset($this->_user)) {
                        return $this->settings = new Settings($this->getPrincipalName());
                }
        }

        /**
         * Get department from catalog.
         * @return string
         */
        public function getDepartment()
        {
                if (isset($this->_user)) {
                        if (($department = $this->catalog->getAttribute($this->getPrincipalName(), 'department'))) {
                                return $department[0]['department'][0];
                        }
                }
        }

        /**
         * Set user primary group.
         * @param string $group The primary group name.
         */
        public function setPrimaryGroup($group)
        {
                $this->_group = $group;
        }

        /**
         * Get user primary group.
         * 
         * If primary group is unset, then the trailing part of the department 
         * name is used. The department name is fetched from the catalog.
         * 
         * @return string
         */
        public function getPrimaryGroup()
        {
                if (!isset($this->_group)) {
                        if (($department = $this->getDepartment())) {
                                $parts = explode(';', $department);
                                $this->_group = trim(array_pop($parts));
                        }
                }

                return $this->_group;
        }

}
