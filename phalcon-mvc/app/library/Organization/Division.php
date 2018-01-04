<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Division.php
// Created: 2016-05-13 02:34:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization;

use OpenExam\Library\Organization\DataProvider\Exams as ExamsDataProvider;
use OpenExam\Library\Organization\DataProvider\Roles as RolesDataProvider;
use OpenExam\Library\Organization\DataProvider\Users as UsersDataProvider;
use OpenExam\Models\Exam;

/**
 * The division class.
 * 
 * Represents an organization unit at the division level. This level is 
 * currently the highest within an organization..
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Division extends OrganizationUnit
{

        /**
         * Constructor.
         * @param string $name The division name.
         */
        public function __construct($name)
        {
                parent::__construct($name);
        }

        /**
         * Get all divisions.
         * @return Division[]
         */
        public static function getDivisions()
        {
                if (($find = Exam::find(array(
                            'columns' => 'division',
                            'group'   => 'division'
                    )))) {
                        $result = array();
                        foreach ($find as $exam) {
                                $result[] = new Division($exam['division']);
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
         * Get all departments for this division.
         * @return Department[]
         */
        public function getChildren()
        {
                if (($find = Exam::find(array(
                            'conditions' => "division = :division:",
                            'columns'    => "division,department",
                            'group'      => array(
                                    "division", "department"
                            ),
                            'bind'       => array(
                                    'division' => $this->_name
                            )
                    )))) {
                        $result = array();
                        foreach ($find as $exam) {
                                $result[] = new Department($exam['division'], $exam['department']);
                        }
                        return $result;
                }
        }

        /**
         * Get parent orgnization object.
         * @return Organization
         */
        public function getParent()
        {
                return new Organization();
        }

        /**
         * Get exams data provider for this division.
         * @return ExamsDataProvider
         */
        public function getExams()
        {
                return new ExamsDataProvider(array(
                        'division' => $this->_name
                ));
        }

        /**
         * Get roles data provider for this division.
         * @return RolesDataProvider 
         */
        public function getRoles()
        {
                return new RolesDataProvider(array(
                        'division' => $this->_name
                ));
        }

        /**
         * Get users data provider for this division.
         * @return UsersDataProvider 
         */
        public function getUsers()
        {
                return new UsersDataProvider(array(
                        'division' => $this->_name
                ));
        }

        protected function createCacheKey()
        {
                return sprintf("organization-orgdiv-%s", md5($this->_name));
        }

}
