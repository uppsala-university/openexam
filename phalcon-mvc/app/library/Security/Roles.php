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
 * Roles are aquired either system wide or bound to an object (identified
 * by the object ID in the model). Internal the aquired roles are stored 
 * as:
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
 * $roles->aquire(Roles::corrector, 456);       // aquire role on question object
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
         * @var array 
         */
        private $_roles = array();

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

        public function __toString()
        {
                return var_export($this->_roles, true);
        }

        /**
         * Add role to collection.
         * @param string $role The role to add.
         * @param int $id The object ID.
         */
        public function addRole($role, $id = 0)
        {
                if ($id == 0) {
                        $this->_roles[0][$role] = true;
                } else {
                        $this->_roles[0][$role] = true;
                        $this->_roles[$id][$role] = true;
                }
        }

        /**
         * Remove role from collection.
         * @param string $role The role to remove.
         * @param int $id The object ID.
         */
        public function removeRole($role, $id = 0)
        {
                if (isset($this->_roles[$id])) {
                        if (array_key_exists($role, $this->_roles[$id])) {
                                unset($this->_roles[$id][$role]);
                        }
                }
        }

        /**
         * Check if role exists.
         * @param string $role The role to check.
         * @param int $id The object ID.
         * @return bool
         */
        public function hasRole($role, $id = 0)
        {
                if (isset($this->_roles[$id])) {
                        return array_key_exists($role, $this->_roles[$id]);
                } else {
                        return false;
                }
        }

        /**
         * Get system wide or object specific roles.
         * @param int $id The object ID.
         * @return array
         */
        public function getRoles($id = 0)
        {
                if (isset($this->_roles[$id])) {
                        return array_keys($this->_roles[$id]);
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
         * Clear all aquired roles.
         */
        public function clear()
        {
                $this->_roles = array();
        }

        /**
         * Try to aquire role. Returns true if successful. The object ID is
         * is optional. 
         * 
         * If the object ID is missing, then the role is aquired system wide. 
         * If supplied, then requested role is aquired on that specific object. 
         * 
         * @param string $role The role name.
         * @param int $id The object ID.
         */
        public function aquire($role, $id = 0)
        {
                if ($this->hasRole($role, $id)) {
                        return true;
                }

                // 
                // Get principal name from user service:
                // 
                if ($this->getDI()->has('user')) {
                        $user = $this->getDI()->get('user');
                        if ($user->getPrincipalName() == null) {
                                return false;
                        }
                }

                // 
                // Temporarily disable access control:
                // 
                $rold = $user->setPrimaryRole(Roles::TRUSTED);

                if ($role == self::ADMIN) {
                        $parameters = array(
                                "user = :user:",
                                "bind" => array("user" => $user->getPrincipalName())
                        );
                        if (Admin::count($parameters)) {
                                $this->addRole($role);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::TEACHER) {
                        $parameters = array(
                                "user = :user:",
                                "bind" => array("user" => $user->getPrincipalName())
                        );
                        if (Teacher::count($parameters)) {
                                $this->addRole($role);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::CONTRIBUTOR) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (Contributor::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::DECODER) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (Decoder::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::INVIGILATOR) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (Invigilator::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::STUDENT) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (Student::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::CREATOR) {
                        if ($id != 0) {
                                $parameters = array(
                                        "creator = :user: AND id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "creator = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (Exam::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                } elseif ($role == self::CORRECTOR) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND question_id = :id:",
                                        "bind" => array("user" => $user->getPrincipalName(), "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user->getPrincipalName())
                                );
                        }
                        if (($corrector = Corrector::find($parameters)->getFirst())) {
                                if ($id != 0) {
                                        // 
                                        // Add corrector role on related exam:
                                        // 
                                        $this->addRole($role, $corrector->question->exam->id);
                                }
                                $this->addRole($role, $id);
                                $user->setPrimaryRole($rold);
                                return true;
                        }
                }

                // 
                // Check for custom roles. These are global by nature.
                // 
                if (self::isCustom($role)) {
                        $this->addRole($role);
                        $user->setPrimaryRole($rold);
                        return true;
                }

                // 
                // Role was not aquired.
                // 
                $user->setPrimaryRole($rold);
                return false;
        }

        /**
         * Check if the admin role has been aquired.
         * @return bool
         */
        public function isAdmin()
        {
                return isset($this->_roles[0][self::ADMIN]);
        }

        /**
         * Check if the student role has been aquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStudent($id = 0)
        {
                return isset($this->_roles[$id][self::STUDENT]);
        }

        /**
         * Check if the staff role has been aquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStaff($id = 0)
        {
                if (!isset($this->_roles[$id])) {
                        return false;
                }
                if (count($this->_roles[$id]) == 0) {
                        return false;
                }
                $roles = array_keys($this->_roles[$id]);
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
                return self::isCustom($role) || $role == self::TEACHER || $role == self::ADMIN || $role == self::TRUSTED;
        }

}
