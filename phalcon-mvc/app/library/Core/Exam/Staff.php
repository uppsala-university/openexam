<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Staff.php
// Created: 2016-04-29 02:30:30
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use OpenExam\Models\Role;
use Phalcon\Mvc\User\Component;

/**
 * Information about exam staff.
 * 
 * Provides basic information about staff working on an exam. For finer 
 * grained details, use the model instead.
 * 
 * The data is organized in two main sections: users and roles. The roles 
 * section lists all roles and their users. The users list all users with
 * their assigned roles as an array. The user data is just name and email 
 * addresses.
 * 
 * @property-read array $contributors Get all contributors.
 * @property-read array $correctors Get all correctors.
 * @property-read array $creators Get all creators.
 * @property-read array $decoders Get all decoders.
 * @property-read array $invigilators Get all invigilators.
 * 
 * @property-read array $users Get all users.
 * @property-read array $roles Get all roles.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Staff extends Component
{

        const SECTION_USERS = 'users';
        const SECTION_ROLES = 'roles';

        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * The staff data.
         * @var array 
         */
        private $_data;
        /**
         * Cache key.
         * @var string
         */
        private $_cachekey;
        /**
         * Cache data lifetime.
         * @var int 
         */
        private $_lifetime;

        /**
         * Constructor.
         * @param Exam $exam The exam object.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
                $this->_cachekey = $this->createCacheKey();
                $this->_lifetime = 24 * 3600;
                $this->setData();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_cachekey);
                unset($this->_data);
                unset($this->_exam);
        }

        public function __isset($property)
        {
                if (isset($this->_data[$property])) {
                        return count($this->_data[$property]) != 0;
                }
                if (isset($this->_data['roles'][$property])) {
                        return count($this->_data['roles'][$property]) != 0;
                }
                if (isset($this->_data['users'][$property])) {
                        return count($this->_data['users'][$property]) != 0;
                }
        }

        public function __get($property)
        {
                if (isset($this->_data[$property])) {
                        return $this->_data[$property];
                } elseif (isset($this->_data['roles'][$property])) {
                        return $this->_data['roles'][$property];
                } elseif (isset($this->_data['users'][$property])) {
                        return $this->_data['users'][$property];
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Get staff data.
         * 
         * The default is to return all data from all section. The section can
         * be filtered. The subkey depends on its section, for roles its one of
         * the standard roles and for users its the user principal.
         * 
         * @param string $section The main section (either on of the SECTION_XXX constants).
         * @param string $subkey The sub section withing main section.
         * @return array
         */
        public function getData($section = null, $subkey = null)
        {
                if (!isset($section)) {
                        if (isset($this->_data)) {
                                return $this->_data;
                        }
                } elseif (!isset($subkey)) {
                        if (isset($this->_data[$section])) {
                                return $this->_data[$section];
                        }
                } else {
                        if (isset($this->_data[$section][$subkey])) {
                                return $this->_data[$section][$subkey];
                        }
                }
        }

        /**
         * Get all roles.
         * 
         * By default all roles and their user data is returned.
         * 
         * @param bool $keys Return only the role names.
         * @return array
         */
        public function getRoles($keys = false)
        {
                if ($keys) {
                        return array_keys($this->_data['roles']);
                } else {
                        $this->_data['roles'];
                }
        }

        /**
         * Get all users.
         * 
         * By default all users and their roles is returned.
         * 
         * @param bool $keys Return only the user principals.
         * @return array
         */
        public function getUsers($keys = false)
        {
                if ($keys) {
                        return array_keys($this->_data['users']);
                } else {
                        $this->_data['users'];
                }
        }

        /**
         * Get user data.
         * @param string $user The user principal name.
         * @return array
         */
        public function getUser($user)
        {
                if (isset($this->_data['users'][$user])) {
                        return $this->_data['users'][$user];
                }
        }

        /**
         * Check if role has data.
         * @param string $role The role name.
         * @return boolean
         */
        public function hasRole($role)
        {
                return count($this->_data['roles'][$role]) != 0;
        }

        /**
         * Get role data.
         * 
         * @param string $role The role name.
         * @return array
         */
        public function getRole($role)
        {
                return $this->_data['roles'][$role];
        }

        /**
         * Add user role.
         * @param Role $role The user role.
         */
        public function addRole($role)
        {
                $this->setRole($role->getResourceName(), $role);
                $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
        }

        /**
         * Remove user role.
         * @param Role $role The user role.
         */
        public function removeRole($role)
        {
                $this->clearRole($role->getResourceName(), $role);
                $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
        }

        /**
         * Create cache key.
         * @return string
         */
        private function createCacheKey()
        {
                return sprintf("staff-exam-%d", $this->_exam->id);
        }

        /**
         * Set staff data (from cache or aggregated).
         */
        private function setData()
        {
                if ($this->cache->exists($this->_cachekey, $this->_lifetime)) {
                        $this->_data = $this->cache->get($this->_cachekey);
                } else {
                        $this->setStaff();
                        $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
                }
        }

        /**
         * Set staff data.
         */
        private function setStaff()
        {
                foreach ($this->_exam->contributors as $role) {
                        $this->setRole(Roles::CONTRIBUTOR, $role);
                }

                foreach ($this->_exam->decoders as $role) {
                        $this->setRole(Roles::DECODER, $role);
                }

                foreach ($this->_exam->invigilators as $role) {
                        $this->setRole(Roles::INVIGILATOR, $role);
                }

                foreach ($this->_exam->questions as $question) {
                        foreach ($question->correctors as $role) {
                                $this->setRole(Roles::CORRECTOR, $role);
                        }
                }

                $principal = $this->catalog->getPrincipal(
                    $this->_exam->creator, Principal::ATTR_PN, null, array(
                        Principal::ATTR_NAME,
                        Principal::ATTR_MAIL
                ));

                $this->setRole(Roles::CREATOR, $principal, $this->_exam->creator);
        }

        /**
         * Set user role data.
         * 
         * @param string $name The role name.
         * @param Role $role The role model.
         * @param string $user The user principal name (optional).
         */
        private function setRole($name, $role, $user = null)
        {
                if (isset($user)) {
                        $role->user = $user;
                }

                // 
                // Insert roles data:
                // 
                if (!isset($this->_data['roles'][$name][$role->user])) {
                        $this->_data['roles'][$name][$role->user] = array(
                                'name' => $role->name,
                                'mail' => $role->mail
                        );
                }

                // 
                // Insert users data:
                // 
                if (!isset($this->_data['users'][$role->user])) {
                        $this->_data['users'][$role->user] = array(
                                'name' => $role->name,
                                'mail' => $role->mail,
                                'role' => array()
                        );
                }

                // 
                // Insert user role if not exist.
                // 
                if (!in_array($name, $this->_data['users'][$role->user]['role'])) {
                        $this->_data['users'][$role->user]['role'][] = $name;
                }
        }

        /**
         * Clear user role data.
         * 
         * @param string $name The role name.
         * @param Role $role The role model.
         * @param string $user The user principal name (optional).
         */
        private function clearRole($name, $role, $user = null)
        {
                if (isset($user)) {
                        $role->user = $user;
                }

                // 
                // Remove roles data:
                // 
                if (isset($this->_data['roles'][$name][$role->user])) {
                        unset($this->_data['roles'][$name][$role->user]);
                }

                // 
                // Remove users data:
                // 
                if (($key = array_search($name, $this->_data['users'][$role->user]['role'])) !== false) {
                        unset($this->_data['users'][$role->user]['role'][$key]);
                }

                // 
                // Remove user role if empty.
                //                 
                if (count($this->_data['users'][$role->user]['role']) == 0) {
                        unset($this->_data['users'][$role->user]);
                }
                if (count($this->_data['roles'][$name]) == 0) {
                        unset($this->_data['roles'][$name]);
                }
        }

}
