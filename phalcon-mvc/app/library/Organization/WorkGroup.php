<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    WorkGroup.php
// Created: 2016-05-13 04:34:03
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization;

use OpenExam\Library\Organization\DataProvider\Exams as ExamsDataProvider;
use OpenExam\Library\Organization\DataProvider\Roles as RolesDataProvider;
use OpenExam\Library\Organization\DataProvider\Users as UsersDataProvider;
use OpenExam\Models\Exam;

/**
 * The work group class.
 * 
 * Represents an organization unit at the workgroup level. This level is the
 * currently lowest in the organization hierarchy having department as its
 * parent.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class WorkGroup extends OrganizationUnit
{

        /**
         * The parent division name.
         * @var string 
         */
        private $_division;
        /**
         * The parent department name.
         * @var string 
         */
        private $_department;

        /**
         * Constructor.
         * @param string $division The parent division name.
         * @param string $department The parent department name.
         * @param string $name The group name.
         */
        public function __construct($division, $department, $name)
        {
                $this->_division = $division;
                $this->_department = $department;
                parent::__construct($name);
        }

        /**
         * Get all groups.
         * @return WorkGroup
         */
        public static function getGroups()
        {
                if (($find = Exam::find(array(
                            'columns' => "division,department,workgroup",
                            'group'   => array("division", "department", "workgroup")
                    )))) {
                        $result = array();
                        foreach ($find as $exam) {
                                $result[] = new WorkGroup($exam['division'], $exam['department'], $exam['workgroup']);
                        }
                        return $result;
                }
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
         * Get parent department name.
         * @return string
         */
        public function getDepartment()
        {
                return $this->_department;
        }

        /**
         * Get parent department object.
         * @return Department
         */
        public function getParent()
        {
                return new Department($this->_division, $this->_department);
        }

        /**
         * Get exams data provider for this work group.
         * @return ExamsDataProvider
         */
        public function getExams()
        {
                return new ExamsDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_department,
                        'workgroup'  => $this->_name
                ));
        }

        /**
         * Get roles data provider for this work group.
         * @return RolesDataProvider 
         */
        public function getRoles()
        {
                return new RolesDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_department,
                        'workgroup'  => $this->_name
                ));
        }

        /**
         * Get users data provider for this work group.
         * @return UsersDataProvider 
         */
        public function getUsers()
        {
                return new UsersDataProvider(array(
                        'division'   => $this->_division,
                        'department' => $this->_department,
                        'workgroup'  => $this->_name
                ));
        }

        /**
         * Get child objects.
         * @return array
         */
        public function getChildren()
        {
                return array();
        }

        /**
         * Check if this object has child objects.
         * 
         * Work groups are the smallest organization unit and doesn't have
         * any child objects.
         * 
         * @return boolean
         */
        public function hasChildren()
        {
                return false;
        }

        protected function createCacheKey()
        {
                return sprintf("organization-orggrp-%s", md5($this->_division . $this->_department . $this->_name));
        }

}
