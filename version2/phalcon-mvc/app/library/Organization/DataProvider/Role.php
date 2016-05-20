<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Role.php
// Created: 2016-05-16 21:36:56
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Database\Exception as DatabaseException;
use OpenExam\Library\Organization\OrganizationUnit;
use OpenExam\Library\Security\Roles as SecurityRoles;
use Phalcon\Mvc\User\Component;

/**
 * The role data provider.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Role extends Component
{

        /**
         * @var RoleData 
         */
        private $_data;
        /**
         * The role name.
         * @var string 
         */
        private $_role;
        /**
         * The display name.
         * @var string 
         */
        private $_name;

        /**
         * Constructor.
         * @param string $conditions The query conditions.
         * @param string $role The role name.
         * @param string $name The display name (e.g. Teachers).
         */
        public function __construct($conditions, $role, $name)
        {
                $this->_role = $role;
                $this->_name = $name;

                $this->_data = RoleData::create($this->_role, $conditions);
        }

        /**
         * Get role name.
         * @return string
         */
        public function getRole()
        {
                return $this->_role;
        }

        /**
         * Get display name.
         * @return string
         */
        public function getName()
        {
                return $this->_name;
        }

        /**
         * Decorate returned users with name and mail from catalog service.
         * @param boolean $enable Enable or disable.
         */
        public function addDecoration($enable = true)
        {
                $this->_data->addDecoration($enable);
        }

        /**
         * Get whether returned user should be decorated.
         * @return boolean
         */
        public function hasDecoration()
        {
                return $this->_data->hasDecoration();
        }

        /**
         * Get number of users having this role.
         * @return int
         */
        public function getSize()
        {
                return $this->_data->getSize();
        }

        /**
         * Get user data for this role.
         * @return array
         */
        public function getData()
        {
                return $this->_data->getData();
        }

}

// 
// Internal used classes:
// 

/**
 * @internal Abstract base class for role data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class RoleData extends Component
{

        private $_cachekey;
        private $_lifetime;
        protected $_role;
        protected $_cond;
        protected $_type;
        private $_data;
        private $_decorate = true;

        protected function __construct($role, $cond, $type)
        {
                $this->_role = $role;
                $this->_cond = $cond;
                $this->_type = $type;

                $this->_cachekey = $this->createCacheKey();
                $this->_lifetime = OrganizationUnit::CACHE_ENTRY_LIFETIME;

                if ($this->cache->exists($this->_cachekey, $this->_lifetime)) {
                        $this->_data = $this->cache->get($this->_cachekey, $this->_lifetime);
                } else {
                        $this->_data = array('size' => null, 'data' => null);
                }
        }

        public function __destruct()
        {
                $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
        }

        public function addDecoration($enable = true)
        {
                $this->_decorate = $enable;
        }

        public function hasDecoration()
        {
                return $this->_decorate;
        }

        public static function create($role, $cond)
        {
                switch ($role) {
                        case SecurityRoles::ADMIN:
                        case SecurityRoles::TEACHER:
                                return new GlobalRole($role);
                        case SecurityRoles::CONTRIBUTOR:
                        case SecurityRoles::DECODER:
                        case SecurityRoles::INVIGILATOR:
                                return new ExamRole($role, $cond, Users::TYPE_EMPLOYEE);
                        case SecurityRoles::STUDENT:
                                return new ExamRole($role, $cond, Users::TYPE_STUDENT);
                        case SecurityRoles::CORRECTOR:
                                return new CorrectorRole($role, $cond);
                        case SecurityRoles::CREATOR:
                                return new CreatorRole($role, $cond);
                        case Users::TYPE_STUDENT:
                                return new ExamRole($role, $cond, Users::TYPE_STUDENT);
                        case Users::TYPE_EMPLOYEE:
                                return new EmployeeRole($cond);
                        default:
                                return new SystemUserRole($cond);
                }
        }

        protected function findSize($sql, $role)
        {
                if (!isset($this->_data['size'])) {
                        if (($resultset = $this->dbread->query($sql))) {
                                $this->_data['size'] = $resultset->fetch()[0];
                        } else {
                                throw new DatabaseException("Failed query for $role role.");
                        }
                }
                return $this->_data['size'];
        }

        protected function findData($sql, $role)
        {
                if (!isset($this->_data['data'])) {
                        if (($resultset = $this->dbread->query($sql))) {
                                $this->_data['data'] = $this->filterResults($resultset->fetchAll());
                        } else {
                                throw new DatabaseException("Failed query for $role role.");
                        }
                }
                return $this->_data['data'];
        }

        private function createCacheKey()
        {
                return sprintf("organization-role-%s-%s", $this->_role, md5($this->_cond));
        }

        /**
         * Decorate query result with name/email.
         * @param array $result
         * @return array
         */
        private function filterResults($result)
        {
                for ($i = 0; $i < count($result); ++$i) {
                        if ($this->_decorate) {
                                if (!($principals = $this->catalog->getPrincipal($result[$i]['user'], Principal::ATTR_PN))) {
                                        $result[$i]['name'] = '';
                                        $result[$i]['mail'] = '';
                                } elseif ($principals[0]->mail) {
                                        $result[$i]['name'] = $principals[0]->name;
                                        $result[$i]['mail'] = $principals[0]->mail[0];
                                } else {
                                        $result[$i]['name'] = $principals[0]->name;
                                        $result[$i]['mail'] = '';
                                }
                        }

                        unset($result[$i][0]);
                        unset($result[$i][1]);
                }

                return $result;
        }

        protected function hasConditions()
        {
                return strstr($this->_cond, 'AND');
        }

        public abstract function getData();

        public abstract function getSize();
}

/**
 * @internal Global role data (e.g. admin or teacher).
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class GlobalRole extends RoleData
{

        const SQL_SIZE = "SELECT COUNT(DISTINCT user) FROM %ss";
        const SQL_DATA = "SELECT DISTINCT user, '%s' AS type FROM %ss";

        public function __construct($role)
        {
                parent::__construct($role, "", Users::TYPE_EMPLOYEE);
        }

        public function getData()
        {
                return parent::findData(sprintf(self::SQL_DATA, $this->_type, $this->_role), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf(self::SQL_SIZE, $this->_role), $this->_role);
        }

}

/**
 * @internal Exam role data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ExamRole extends RoleData
{

        const SQL_SIZE = "SELECT COUNT(DISTINCT s.user) FROM %ss AS s INNER JOIN exams AS e ON s.exam_id = e.id WHERE %s";
        const SQL_DATA = "SELECT DISTINCT s.user, '%s' AS type FROM %ss AS s INNER JOIN exams AS e ON s.exam_id = e.id WHERE %s";

        public function __construct($role, $cond, $type)
        {
                parent::__construct($role, $cond, $type);
        }

        public function getData()
        {
                return parent::findData(sprintf(self::SQL_DATA, $this->_type, $this->_role, $this->_cond), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf(self::SQL_SIZE, $this->_role, $this->_cond), $this->_role);
        }

}

/**
 * @internal Corrector role data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CorrectorRole extends RoleData
{

        const SQL_SIZE = "SELECT COUNT(DISTINCT c.user) FROM correctors AS c INNER JOIN questions AS q ON c.question_id = q.id INNER JOIN exams AS e ON q.exam_id = e.id  WHERE %s";
        const SQL_DATA = "SELECT DISTINCT c.user, '%s' AS type FROM correctors AS c INNER JOIN questions AS q ON c.question_id = q.id INNER JOIN exams AS e ON q.exam_id = e.id  WHERE %s";

        public function __construct($role, $cond)
        {
                parent::__construct($role, $cond, Users::TYPE_EMPLOYEE);
        }

        public function getData()
        {
                return parent::findData(sprintf(self::SQL_DATA, $this->_type, $this->_cond), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf(self::SQL_SIZE, $this->_cond), $this->_role);
        }

}

/**
 * @internal Creator role data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CreatorRole extends RoleData
{

        const SQL_SIZE = "SELECT COUNT(DISTINCT creator) FROM exams AS e WHERE %s";
        const SQL_DATA = "SELECT DISTINCT creator AS user, '%s' AS type FROM exams AS e WHERE %s";

        public function __construct($role, $cond)
        {
                parent::__construct($role, $cond, Users::TYPE_EMPLOYEE);
        }

        public function getData()
        {
                return parent::findData(sprintf(self::SQL_DATA, $this->_type, $this->_cond), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf(self::SQL_SIZE, $this->_cond), $this->_role);
        }

}

/**
 * @internal Employee user data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class EmployeeRole extends RoleData
{

        private $_sql;

        protected function __construct($cond)
        {
                parent::__construct(Users::TYPE_EMPLOYEE, $cond, Users::TYPE_EMPLOYEE);

                $queries = array();

                $queries[] = sprintf(CreatorRole::SQL_DATA, $this->_type, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, $this->_type, SecurityRoles::CONTRIBUTOR, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, $this->_type, SecurityRoles::DECODER, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, $this->_type, SecurityRoles::INVIGILATOR, $this->_cond);
                $queries[] = sprintf(CorrectorRole::SQL_DATA, $this->_type, $this->_cond);

                if (!$this->hasConditions()) {
                        $queries[] = sprintf(GlobalRole::SQL_DATA, $this->_type, SecurityRoles::ADMIN);
                        $queries[] = sprintf(GlobalRole::SQL_DATA, $this->_type, SecurityRoles::TEACHER);
                }

                $this->_sql = implode(" UNION ", $queries);
        }

        public function getData()
        {
                return parent::findData(sprintf("%s ORDER BY user", $this->_sql), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf("SELECT COUNT(DISTINCT user) FROM (%s)", $this->_sql), $this->_role);
        }

}

/**
 * @internal System user data (any user with login).
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SystemUserRole extends RoleData
{

        private $_sql;

        protected function __construct($cond)
        {
                parent::__construct(SecurityRoles::SYSTEM, $cond, null);

                $queries = array();

                $queries[] = sprintf(CreatorRole::SQL_DATA, Users::TYPE_EMPLOYEE, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, Users::TYPE_EMPLOYEE, SecurityRoles::CONTRIBUTOR, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, Users::TYPE_EMPLOYEE, SecurityRoles::DECODER, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, Users::TYPE_EMPLOYEE, SecurityRoles::INVIGILATOR, $this->_cond);
                $queries[] = sprintf(ExamRole::SQL_DATA, Users::TYPE_STUDENT, SecurityRoles::STUDENT, $this->_cond);
                $queries[] = sprintf(CorrectorRole::SQL_DATA, Users::TYPE_EMPLOYEE, $this->_cond);

                if (!$this->hasConditions()) {
                        $queries[] = sprintf(GlobalRole::SQL_DATA, Users::TYPE_EMPLOYEE, SecurityRoles::ADMIN);
                        $queries[] = sprintf(GlobalRole::SQL_DATA, Users::TYPE_EMPLOYEE, SecurityRoles::TEACHER);
                }

                $this->_sql = implode(" UNION ", $queries);
        }

        public function getData()
        {
                return parent::findData(sprintf("%s ORDER BY user", $this->_sql), $this->_role);
        }

        public function getSize()
        {
                return parent::findSize(sprintf("SELECT COUNT(DISTINCT user) FROM (%s) AS Users", $this->_sql), $this->_role);
        }

}
