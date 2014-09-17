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
use OpenExam\Models\Question;
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
 * - corrector          : object specific (bound to question)
 * 
 * This class defines all builtin roles. These are further classified as
 * admin, staff and student. The staff roles includes any builtin role except
 * student or admin. Custom roles are any role not defined by this class.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Roles extends Component
{

        /**
         * The admin role (system wide).
         */
        const admin = 'admin';
        /**
         * The teacher role (system wide).
         */
        const teacher = 'teacher';
        /**
         * The creator role (bound to specific exam).
         */
        const creator = 'creator';
        /**
         * The contributor role (bound to specific exam).
         */
        const contributor = 'contributor';
        /**
         * The invigilator role (bound to specific exam).
         */
        const invigilator = 'invigilator';
        /**
         * The decoder role (bound to specific exam).
         */
        const decoder = 'decoder';
        /**
         * The corrector role (bound to specific question).
         */
        const corrector = 'corrector';
        /**
         * The student role (bound to specific exam).
         */
        const student = 'student';

        /**
         * @var array 
         */
        private $roles = array();

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
                return var_export($this->roles, true);
        }

        /**
         * Add role to collection.
         * @param string $role The role to add.
         * @param int $id The object ID.
         */
        public function addRole($role, $id = 0)
        {
                if ($id == 0) {
                        $this->roles[0][$role] = true;
                } else {
                        $this->roles[0][$role] = true;
                        $this->roles[$id][$role] = true;
                }
        }

        /**
         * Remove role from collection.
         * @param string $role The role to remove.
         * @param int $id The object ID.
         */
        public function removeRole($role, $id = 0)
        {
                if (isset($this->roles[$id])) {
                        if (array_key_exists($role, $this->roles[$id])) {
                                unset($this->roles[$id][$role]);
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
                if (isset($this->roles[$id])) {
                        return array_key_exists($role, $this->roles[$id]);
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
                if (isset($this->roles[$id])) {
                        return array_keys($this->roles[$id]);
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
                return $this->roles;
        }

        /**
         * Clear all aquired roles.
         */
        public function clear()
        {
                $this->roles = array();
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
                if (($user = $this->user->getPrincipalName()) == null) {
                        return false;
                }

                if ($role == self::admin) {
                        $parameters = array(
                                "user = :user:",
                                "bind" => array("user" => $user)
                        );
                        if (Admin::count($parameters)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::teacher) {
                        $parameters = array(
                                "user = :user:",
                                "bind" => array("user" => $user)
                        );
                        if (Teacher::count($parameters)) {
                                $this->addRole($role);
                                return true;
                        }
                }
                if ($role == self::contributor) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Contributor::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }
                if ($role == self::decoder) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Decoder::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }
                if ($role == self::invigilator) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Invigilator::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }
                if ($role == self::student) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND exam_id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Student::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }
                if ($role == self::creator) {
                        if ($id != 0) {
                                $parameters = array(
                                        "creator = :user: AND id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "creator = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Exam::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }
                if ($role == self::corrector) {
                        if ($id != 0) {
                                $parameters = array(
                                        "user = :user: AND question_id = :id:",
                                        "bind" => array("user" => $user, "id" => $id)
                                );
                        } else {
                                $parameters = array(
                                        "user = :user:",
                                        "bind" => array("user" => $user)
                                );
                        }
                        if (Corrector::count($parameters) > 0) {
                                $this->addRole($role, $id);
                                return true;
                        }
                }

                // 
                // Role was not aquired.
                // 
                return false;
        }

        /**
         * Check if the admin role has been aquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isAdmin($id = 0)
        {
                return isset($this->roles[$id][self::admin]);
        }

        /**
         * Check if the student role has been aquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStudent($id = 0)
        {
                return isset($this->roles[$id][self::student]);
        }

        /**
         * Check if the staff role has been aquired.
         * @param int $id The object ID.
         * @return bool
         */
        public function isStaff($id = 0)
        {
                if (!isset($this->roles[$id])) {
                        return false;
                }
                if (count($this->roles[$id]) == 0) {
                        return false;
                }
                $roles = array_keys($this->roles[$id]);
                $staff = array(
                        self::teacher,
                        self::contributor,
                        self::corrector,
                        self::creator,
                        self::decoder,
                        self::invigilator
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
                return $class->getConstant($role) === false;
        }

}
