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

namespace OpenExam\Library\Core\Security;

use OpenExam\Models\Admin,
    OpenExam\Models\Contributor,
    OpenExam\Models\Decoder,
    OpenExam\Models\Invigilator,
    OpenExam\Models\Student,
    OpenExam\Models\Teacher;

/**
 * Manage roles for user session.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Roles extends \Phalcon\Mvc\User\Plugin
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

        /**
         * Try to aquire role. Returns true if successful. The exam is
         * is optional.
         * 
         * @param string $role The role name.
         * @param int $exam The exam ID.
         */
        public function aquire($role, $exam = 0)
        {
                if ($this->hasRole($role)) {
                        return true;
                }

                // TODO: Correct access to logged on user ???
                $user = $this->session->get('auth')['name'];

                if ($role == self::admin) {
                        if (Admin::count("user='" . $user . "'")) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::teacher) {
                        if (Teacher::count("user='" . $user . "'")) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::contributor) {
                        if (Contributor::count("user='" . $user . "' AND exam_id=" . $exam)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::decoder) {
                        if (Decoder::count("user='" . $user . "' AND exam_id=" . $exam)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::invigilator) {
                        if (Invigilator::count("user='" . $user . "' AND exam_id=" . $exam)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::student) {
                        if (Student::count("user='" . $user . "' AND exam_id=" . $exam)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::creator) {
                        if (Exam::count("creator='" . $user . "' AND id=" . $exam)) {
                                $this->addRole($role);
                                return true;
                        }
                }

                // 
                // Role was not aquired.
                // 
                return false;
        }

}
