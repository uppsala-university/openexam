<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Roles.php
// Created: 2014-08-25 09:34:37
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

/**
 * Manage roles for user session.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Roles
{

        const admin = 'admin';
        const teacher = 'teacher';
        const creator = 'creator';
        const contributor = 'contributor';
        const invigilator = 'invigilator';
        const decoder = 'decoder';
        const corrector = 'corrector';
        const student = 'student';

        private $roles = array();

        /**
         * Constructor.
         * @param array $roles
         */
        public function __construct($roles = array())
        {
                $this->roles = $roles;
        }

        public function __get($name)
        {
                return $this->hasRole($name);
        }

        /**
         * Add role to collection.
         * @param string $role
         */
        public function addRole($role)
        {
                $this->roles[$role] = true;
        }

        /**
         * Remove role from collection.
         * @param string $role
         */
        public function removeRole($role)
        {
                if (array_key_exists($role, $this->roles)) {
                        unset($this->roles[$role]);
                }
        }

        /**
         * Check if role exists.
         * @param string $role
         * @return bool
         */
        public function hasRole($role)
        {
                return array_key_exists($role, $this->roles);
        }

        /**
         * Get all roles in collection.
         * @return array
         */
        public function getRoles()
        {
                return array_keys($this->roles);
        }

}
