<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Roles.php
// Created: 2014-08-25 09:34:37
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Security;

use OpenExam\Models\Admin;
use OpenExam\Models\Contributor;
use OpenExam\Models\Corrector;
use OpenExam\Models\Decoder;
use OpenExam\Models\Exam;
use OpenExam\Models\Invigilator;
use OpenExam\Models\Student;
use OpenExam\Models\Teacher;
use Phalcon\Mvc\User\Component;
use ReflectionClass;

/**
 * Manage roles for user session.
 * 
 * Roles are acquired either system wide or bound to an object (identified by 
 * the object ID in the model). Acquired roles are stored in the cache that 
 * should be invalidated whenever user roles are added or deleted. The mutators
 * 
 * Internal the acquired roles are stored as:
 * 
 * <code>
 * $roles[0][role]              // System wide role.
 * $roles[id][role]             // Object specific role.
 * </code>
 * 
 * <code>
 * $roles->getRoles();          // Get system wide roles.
 * $roles->getRoles(11);        // Get object specific roles.
 * </code>
 * 
 * For example, $roles[0]['contributor'] means that the authenticated
 * user (stored in session object) has the 'contributor' role on at least
 * one exam.
 * 
 * Aquiring a role for an specific object will also automatic grant that user
 * the same role system wide.
 * 
 * These are the defined roles along with their object specific binding:
 * 
 * - admin              : system wide
 * - teacher            : system wide
 * - creator            : object specific (bound to exam)
 * - contributor        : object specific (bound to exam) 
 * - invigilator        : object specific (bound to exam)
 * - decoder            : object specific (bound to exam) 
 * - student            : object specific (bound to exam) 
 * - corrector          : object specific (bound to exam and question)
 * 
 * This class defines all builtin roles. These are further classified as
 * admin, staff and student. The staff roles includes any builtin role except
 * student or admin. Custom roles are any role not defined by this class.
 * 
 * The corrector role is automatic injected for the exam containing the
 * question for whom the question belongs:
 * 
 * <code>
 * $exam  = ...         // (id = 123, ...)
 * $quest = ...         // (id = 456, exam_id = 123, ...)
 * 
 * $roles->hasRole(Roles::corrector, 123);      // false (on exam)
 * $roles->acquire(Roles::corrector, 456);       // acquire role on question object
 * $roles->hasRole(Roles::corrector, 123);      // true (on exam)
 * $roles->hasRole(Roles::corrector, 456);      // true (on question)
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Roles extends Component
{

        /**
         * The admin role (system wide).
         */
        const ADMIN = 'admin';
        /**
         * The teacher role (system wide).
         */
        const TEACHER = 'teacher';
        /**
         * The creator role (bound to specific exam).
         */
        const CREATOR = 'creator';
        /**
         * The contributor role (bound to specific exam).
         */
        const CONTRIBUTOR = 'contributor';
        /**
         * The invigilator role (bound to specific exam).
         */
        const INVIGILATOR = 'invigilator';
        /**
         * The decoder role (bound to specific exam).
         */
        const DECODER = 'decoder';
        /**
         * The corrector role (bound to specific question).
         */
        const CORRECTOR = 'corrector';
        /**
         * The student role (bound to specific exam).
         */
        const STUDENT = 'student';
        /**
         * Control ACL system (internal role).
         */
        const TRUSTED = 'cacls';
        /**
         * Model cache maintenance (internal role).
         */
        const SYSTEM = 'system';

        /**
         * @var array 
         */
        private $_roles = array();
        /**
         * Cached data has been modified flag.
         * @var bool 
         */
        private $_dirty = false;
        /**
         * The cache data key.
         * @var string 
         */
        private $_rckey = false;

        /**
         * Constructor.
         * 
         * <code>
         * $roles = new Roles(array(2 => array(Roles::contributor, Roles::decoder));
         * $roles = new Roles(array(2 => array(Roles::contributor => true, Roles::decoder => true));
         * </code>
         * @param array $roles
         */
        public function __construct($roles = array())
        {
                if ($this->getDI()->has('user')) {
                        $this->_rckey = sprintf("roles-acquired-%s", $this->getDI()->get('user')->getPrincipalName());
                }
                if ($this->cache->exists($this->_rckey)) {
                        $this->_roles = $this->cache->get($this->_rckey);
                }

                if (count($roles) != 0) {
                        foreach ($roles as $id => $arr) {
                                foreach ($arr as $key => $val) {
                                        if (is_numeric($key)) {
                                                $this->addRole($val, $id);
                                        } else {
                                                $this->addRole($key, $id);
                                        }
                                }
                        }
                }
        }

        public function __destruct()
        {
                if ($this->_dirty) {
                        $this->cache->save($this->_rckey, $this->_roles);
                }
        }

        public function __toString()
        {
                return var_export($this->_roles, true);
        }

        /**
         * Add role to collection.
         * 
         * @param string $role The role to add.
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         */
        public function addRole($role, $id = 0, $bind = 'native')
        {
                $this->_roles[$bind][0][$role] = true;
                $this->_roles[$bind][$id][$role] = true;
                $this->_dirty = true;
        }

        /**
         * Remove role from collection.
         * 
         * @param string $role The role to remove.
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         */
        public function removeRole($role, $id = 0, $bind = 'native')
        {
                if (isset($this->_roles[$bind][$id])) {
                        if (array_key_exists($role, $this->_roles[$bind][$id])) {
                                unset($this->_roles[$bind][$id][$role]);
                                $this->_dirty = true;
                        }
                }
        }

        /**
         * Check if role exists.
         * 
         * @param string $role The role to check.
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         * @return bool
         */
        public function hasRole($role, $id = 0, $bind = 'native')
        {
                if (isset($this->_roles[$bind][$id])) {
                        return array_key_exists($role, $this->_roles[$bind][$id]);
                } else {
                        return false;
                }
        }

        /**
         * Get system wide or object specific roles.
         * 
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         * @return array
         */
        public function getRoles($id = 0, $bind = 'native')
        {
                if (isset($this->_roles[$bind][$id])) {
                        return array_keys($this->_roles[$bind][$id]);
                } else {
                        return array();
                }
        }

        /**
         * Get all roles.
         * @return array
         */
        public function getAllRoles()
        {
                return $this->_roles;
        }

        /**
         * Clear all acquired roles.
         */
        public function clear()
        {
                $this->_roles = array();
                $this->_dirty = true;
        }

        /**
         * Check if caller is admin.
         * @return bool
         */
        private function hasAdmin()
        {
                return Admin::count(array(
                            "user = :user:",
                            "bind" => array(
                                    "user" => $this->user->getPrincipalName()
                            )
                    )) > 0;
        }

        /**
         * Check if caller is teacher.
         * @return bool
         */
        private function hasTeacher()
        {
                return Teacher::count(array(
                            "user = :user:",
                            "bind" => array(
                                    "user" => $this->user->getPrincipalName()
                            )
                    )) > 0;
        }

        /**
         * Check if caller is contributor.
         * 
         * @param int $id The object ID.
         * @return bool
         */
        private function hasContributor($id = 0)
        {
                if ($id != 0) {
                        return Contributor::count(array(
                                    "user = :user: AND exam_id = :id:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName(),
                                            "id"   => $id
                                    )
                            )) > 0;
                } else {
                        return Contributor::count(array(
                                    "user = :user:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName()
                                    )
                            )) > 0;
                }
        }

        /**
         * Check if caller is decoder.
         * 
         * @param int $id The object ID.
         * @return bool
         */
        private function hasDecoder($id = 0)
        {
                if ($id != 0) {
                        return Decoder::count(array(
                                    "user = :user: AND exam_id = :id:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName(),
                                            "id"   => $id
                                    )
                            )) > 0;
                } else {
                        return Decoder::count(array(
                                    "user = :user:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName()
                                    )
                            )) > 0;
                }
        }

        /**
         * Check if caller is invigilator.
         * 
         * @param int $id The object ID.
         * @return bool
         */
        private function hasInvigilator($id = 0)
        {
                if ($id != 0) {
                        return Invigilator::count(array(
                                    "user = :user: AND exam_id = :id:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName(),
                                            "id"   => $id
                                    )
                            )) > 0;
                } else {
                        return Invigilator::count(array(
                                    "user = :user:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName()
                                    )
                            )) > 0;
                }
        }

        /**
         * Check if caller is student.
         * 
         * @param int $id The object ID.
         * @return bool
         */
        private function hasStudent($id = 0)
        {
                if ($id != 0) {
                        return Student::count(array(
                                    "user = :user: AND exam_id = :id:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName(),
                                            "id"   => $id
                                    )
                            )) > 0;
                } else {
                        return Student::count(array(
                                    "user = :user:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName()
                                    )
                            )) > 0;
                }
        }

        /**
         * Check if caller is exam creator.
         * 
         * @param int $id The object ID.
         * @return bool
         */
        private function hasCreator($id = 0)
        {
                if ($id != 0) {
                        return Exam::count(array(
                                    "creator = :user: AND id = :id:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName(),
                                            "id"   => $id
                                    )
                            )) > 0;
                } else {
                        return Exam::count(array(
                                    "creator = :user:",
                                    "bind" => array(
                                            "user" => $this->user->getPrincipalName()
                                    )
                            )) > 0;
                }
        }

        /**
         * Check if caller is corrector.
         * 
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         * @return bool
         */
        private function hasCorrector($id = 0, $bind = 'native')
        {
                // 
                // Keep primary role. Will be reset on method return.
                // 
                $role = $this->user->getPrimaryRole();

                // 
                // The corrector role is bound to a question, but if the bind
                // parameter is passed then the role can be checked againt non-native
                // model. Currently bind is limited to exam.
                // 

                try {
                        if ($bind == 'native') {
                                if ($id != 0) {
                                        return Corrector::count(array(
                                                    "user = :user: AND question_id = :id:",
                                                    "bind" => array(
                                                            "user" => $this->user->getPrincipalName(),
                                                            "id"   => $id
                                                    )
                                            )) > 0;
                                } else {
                                        return Corrector::count(array(
                                                    "user = :user:",
                                                    "bind" => array(
                                                            "user" => $this->user->getPrincipalName()
                                                    )
                                            )) > 0;
                                }
                        } elseif ($bind == 'exam') {
                                $role = $this->user->setPrimaryRole(self::CORRECTOR);

                                if ($id != 0) {
                                        return Exam::count(array(
                                                    "id = :id:",
                                                    "bind" => array(
                                                            "id" => $id
                                                    )
                                            )) > 0;
                                } else {
                                        return Exam::count() > 0;
                                }
                        }
                } finally {
                        $this->user->setPrimaryRole($role);
                }
        }

        /**
         * Try to acquire role. 
         * 
         * Returns true if requested role was successful acquired, otherwise
         * false. 
         * 
         * The role is acquired system wide if object ID is missing. Otherwise
         * requested role is acquired on that object. The corrector role is
         * native bound to questions, pass exam as bind parameter to acquire 
         * that role on exam instead.
         * 
         * <code>
         * // Caller is decoder on at least one exam:
         * $roles->acquire(Roles::DECODER);
         * 
         * // Caller is decoder on exam 29883:
         * $roles->acquire(Roles::DECODER, 29883);
         * 
         * // Caller is corrector:
         * $roles->acquire(Roles::CORRECTOR);
         * 
         * // Caller is corrector of question 56635:
         * $roles->acquire(Roles::CORRECTOR, 56635);
         * 
         * // Caller is corrector on exam 29883:
         * $roles->acquire(Roles::CORRECTOR, 29883, 'exam');
         * </code>
         * 
         * @param string $role The role name.
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         * 
         * @return bool
         */
        public function acquire($role, $id = 0, $bind = 'native')
        {
                if ($this->hasRole($role, $id, $bind)) {
                        return true;
                }

                // 
                // Get principal name from user service:
                // 
                if ($this->user->getPrincipalName() == null) {
                        return false;
                }

                // 
                // Temporarily disable access control:
                // 
                $rold = $this->user->setPrimaryRole(Roles::TRUSTED);

                // 
                // Try to acquire requested role:
                // 
                try {
                        switch ($role) {
                                case self::ADMIN:
                                        if ($this->hasAdmin()) {
                                                $this->addRole($role);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::TEACHER:
                                        if ($this->hasTeacher()) {
                                                $this->addRole($role);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::CONTRIBUTOR:
                                        if ($this->hasContributor($id)) {
                                                $this->addRole($role, $id);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::DECODER:
                                        if ($this->hasDecoder($id)) {
                                                $this->addRole($role, $id);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::INVIGILATOR:
                                        if ($this->hasInvigilator($id)) {
                                                $this->addRole($role, $id);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::STUDENT:
                                        if ($this->hasStudent($id)) {
                                                $this->addRole($role, $id);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::CREATOR:
                                        if ($this->hasCreator($id)) {
                                                $this->addRole($role, $id);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                case self::CORRECTOR:
                                        if ($this->hasCorrector($id, $bind)) {
                                                $this->addRole($role, $id, $bind);
                                                return true;
                                        } else {
                                                return false;
                                        }
                                default:
                                        // 
                                        // Check for custom roles. These are global by nature.
                                        // 
                                        if (self::isCustom($role)) {
                                                $this->addRole($role);
                                                return true;
                                        }
                        }
                } finally {
                        $this->user->setPrimaryRole($rold);
                }

                // 
                // Failed acquire requested role:
                // 
                return false;
        }

        /**
         * Check if role is acquired.
         * 
         * @param string $role The role name.
         * @param int $id The optional object ID.
         * @param string $bind The model to bind ID against.
         * @return bool
         */
        private function isAcquired($role, $id = 0, $bind = 'native')
        {
                return isset($this->_roles[$bind][$id][$role]);
        }

        /**
         * Check if the admin role has been acquired.
         * @return bool
         */
        public function isAdmin()
        {
                return $this->isAcquired(self::ADMIN);
        }

        /**
         * Check if the teacher role has been acquired.
         * @return bool
         */
        public function isTeacher()
        {
                return $this->isAcquired(self::TEACHER);
        }

        /**
         * Check if the contributor role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isContributor($id = 0)
        {
                return $this->isAcquired(self::CONTRIBUTOR, $id);
        }

        /**
         * Check if the corrector role has been acquired.
         * @param int $id The object ID.
         * @param string $bind The model to bind ID against.
         * @return bool
         */
        public function isCorrector($id = 0, $bind = null)
        {
                return $this->isAcquired(self::CORRECTOR, $id, $bind);
        }

        /**
         * Check if the creator role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isCreator($id = 0)
        {
                return $this->isAcquired(self::CREATOR, $id);
        }

        /**
         * Check if the decoder role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isDecoder($id = 0)
        {
                return $this->isAcquired(self::DECODER, $id);
        }

        /**
         * Check if the invigilator role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isInvigilator($id = 0)
        {
                return $this->isAcquired(self::INVIGILATOR, $id);
        }

        /**
         * Check if the student role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStudent($id = 0)
        {
                return $this->isAcquired(self::STUDENT, $id);
        }

        /**
         * Check if the staff role has been acquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStaff($id = 0)
        {
                if (!isset($this->_roles['native'][$id])) {
                        return false;
                }
                if (count($this->_roles['native'][$id]) == 0) {
                        return false;
                }
                $roles = array_keys($this->_roles['native'][$id]);
                $staff = array(
                        self::TEACHER,
                        self::CONTRIBUTOR,
                        self::CORRECTOR,
                        self::CREATOR,
                        self::DECODER,
                        self::INVIGILATOR
                );
                return count(array_intersect($staff, $roles)) > 0;
        }

        /**
         * Check if role is custom. That is, not one of the predefined in the
         * system.
         * @param string $role The role to check.
         * @return bool
         */
        public static function isCustom($role)
        {
                $class = new ReflectionClass(__CLASS__);
                return in_array($role, $class->getConstants()) == false;
        }

        /**
         * Check if role is global. That is, either custom, admin or teacher.
         * @param string $role
         * @return bool
         */
        public static function isGlobal($role)
        {
                return self::isCustom($role) ||
                    $role == self::TEACHER ||
                    $role == self::ADMIN ||
                    $role == self::TRUSTED ||
                    $role == self::SYSTEM;
        }

}
