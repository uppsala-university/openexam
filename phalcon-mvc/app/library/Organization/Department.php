<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Department.php
// Created: 2016-05-13 04:08:48
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization;

use OpenExam\Library\Organization\DataProvider\Exams as ExamsDataProvider;
use OpenExam\Library\Organization\DataProvider\Roles as RolesDataProvider;
use OpenExam\Library\Organization\DataProvider\Users as UsersDataProvider;
use OpenExam\Models\Exam;

/**
 * The department class.
 * 
 * Represents an organization unit at the department level. This level is 
 * a child of the division.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Department extends OrganizationUnit
{

        /**
         * The parent division name.
         * @var string 
         */
        private $_division;

        /**
         * Constructor.
         * @param string $division The parent division name.
         * @param string $name The department name.
         */
        public function __construct($division, $name)
        {
                $this->_division = $division;
                parent::__construct($name);
        }

        /**
         * Get all departments.
         * @return Department[]
         */
        public static function getDepartments()
        {
                if (($find = Exam::find(array(
                            'columns' => 'division,department',
                            'group'   => array('division', 'department')
                    )))) {
                        $result = array();
                        foreach ($find as $exam) {
                                $result[] = new Department($exam['division'], $exam['department']);
                        }
                        return $result;
                }
        }

        /**
         * Check if this object might have child object.
         * @return boolean
         */
        public function hasChildren()
        {
                return true;
        }

        /**
         * Get all workgroups for this department.
         * @return WorkGroup[]
         */
        public function getChildren()
        {
                if (($find = Exam::find(array(
                            'conditions' => "division = :division: AND department = :department:",
                            'columns'    => "division,department,workgroup",
                            'group'      => array(
                                    "division", "department", "workgroup"
                            ),
                            'bind'       => array(
                                    'division'   => $this->_division,
                                    'department' => $this->_name
                            )
                    )))) {
                        $result = array();
                        foreach ($find as $exam) {
                                $result[] = new WorkGroup($exam['division'], $exam['department'], $exam['workgroup']);
                        }
                        return $result;
                }
        }

        /**
         * Get parent division object.
         * @return Division
         */
        public function getParent()
        {
                return new Division($this->_division);
        }

        /**
         * Get parent division name.
         * @return string
         */
        public function getDivision()
        {
                return $this->_division;
        }

        /**
         * Get exams data provider for this department.
         * @return ExamsDataProvider
         */
        public function getExams()
        {
                return new ExamsDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_name
                ));
        }

        /**
         * Get roles data provider for this department.
         * @return RolesDataProvider 
         */
        public function getRoles()
        {
                return new RolesDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_name
                ));
        }

        /**
         * Get users data provider for this department.
         * @return UsersDataProvider 
         */
        public function getUsers()
        {
                return new UsersDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_name
                ));
        }

        protected function createCacheKey()
        {
                return sprintf("organization-orgdep-%s", md5($this->_division . $this->_name));
        }

}
