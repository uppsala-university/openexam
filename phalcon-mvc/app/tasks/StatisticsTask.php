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
// File:    StatisticsTask.php
// Created: 2016-05-12 14:17:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Organization\Department;
use OpenExam\Library\Organization\Division;
use OpenExam\Library\Organization\Organization;
use OpenExam\Library\Organization\OrganizationUnit;
use OpenExam\Library\Organization\WorkGroup;
use OpenExam\Models\Exam;

/**
 * System usage (statistics) task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class StatisticsTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'System usage statistics.',
                        'action'   => '--statistics',
                        'usage'    => array(
                                '--list [--division|--department|workgroup]',
                                '--show [--division=name [--department=name [--workgroup=name]]] [--exams] [--roles] [--users]',
                                '--explore',
                                '--summary',
                                '--migrate'
                        ),
                        'options'  => array(
                                '--list'    => 'List available divisions, departments or workgroups.',
                                '--show'    => 'Show statistics data.',
                                '--summary' => 'Show summary.',
                                '--explore' => 'Explorer like view.',
                                '--migrate' => 'Data migration task.',
                                '--exams'   => 'List exams belonging to organization unit.',
                                '--roles'   => 'List roles belonging to organization unit.',
                                '--users'   => 'List users belonging to organization unit.',
                                '--verbose' => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'List all divisions',
                                        'command' => '--list'
                                ),
                                array(
                                        'descr'   => 'Show exam, roles and users statistics for all divisions',
                                        'command' => '--statistics --show --exams --roles --users'
                                )
                        )
                );
        }

        /**
         * Show statistics action.
         * @param array $params
         */
        public function showAction($params = array())
        {
                $this->setOptions($params, 'show');

                if ($this->_options['workgroup']) {
                        $this->showWorkGroup();
                } elseif ($this->_options['department']) {
                        $this->showDepartment();
                } elseif ($this->_options['division']) {
                        $this->showDivision();
                } else {
                        $this->showDivisions();
                }
        }

        /**
         * List statistics action.
         * @param array $params
         */
        public function listAction($params = array())
        {
                $this->setOptions($params, 'list');

                if ($this->_options['workgroup']) {
                        $this->listWorkGroups();
                } elseif ($this->_options['department']) {
                        $this->listDepartments();
                } else {
                        $this->listDivisions();
                }
        }

        /**
         * Data migration action.
         * @param array $params
         */
        public function migrateAction($params = array())
        {
                $this->setOptions($params, 'migrate');

                if (($exams = Exam::find())) {
                        foreach ($exams as $exam) {
                                $parts = explode(";", $exam->orgunit);
                                $parts = array_pad($parts, 3, null);

                                $exam->division = trim($parts[0]);
                                $exam->department = trim($parts[1]);
                                $exam->workgroup = trim($parts[2]);

                                $exam->update();

                                if ($this->_options['verbose']) {
                                        $this->flash->notice(sprintf("%s\t-> [%s, %s, %s]\n", $exam->orgunit, $exam->division, $exam->department, $exam->workgroup));
                                }
                        }
                }
        }

        /**
         * Explore action.
         * @param array $params
         */
        public function exploreAction($params = array())
        {
                $this->setOptions($params, 'explore');

                $result = array();

                $divisions = Division::getDivisions();
                foreach ($divisions as $division) {
                        $result[$division->getName()] = $division->getData();
                        $departments = $division->getChildren();
                        foreach ($departments as $department) {
                                $result[$division->getName()][$department->getName()] = $department->getData();
                                $workgroups = $department->getChildren();
                                foreach ($workgroups as $workgroup) {
                                        $result[$division->getName()][$department->getName()][$workgroup->getName()] = $workgroup->getData();
                                }
                        }
                }

                $this->flash->success(print_r($result, true));
        }

        public function summaryAction($params = array())
        {
                $this->setOptions($params, 'summary');

                $organization = new Organization();
                $roles = $organization->getRoles();
                $exams = $organization->getExams();
                $users = $organization->getUsers();

                $this->flash->success(sprintf("Divisions (%d)", count($organization->getChildren())));
                $this->flash->success(sprintf("Exams (%d)", $exams->getSize()));

                $this->flash->success(sprintf("Roles (%d)", $roles->getSize()));
                foreach ($roles->getData() as $data) {
                        $this->flash->success(sprintf("-> %s: %d", $data['label'], $data['count']));
                }

                $this->flash->success(sprintf("Users (%d)", $users->getSize()));
                $this->flash->success(sprintf("-> Employees: %d", $users->getEmployees()->getSize()));
                $this->flash->success(sprintf("-> Students:  %d", $users->getStudents()->getSize()));
        }

        private function showWorkGroup($group = null)
        {
                if (!isset($group)) {
                        $group = new WorkGroup($this->_options['division'], $this->_options['department'], $this->_options['workgroup']);
                }

                $this->flash->success(sprintf("Workgroup: %s", $group->getName()));

                if ($this->_options['exams']) {
                        $this->showExams($group);
                }
                if ($this->_options['roles']) {
                        $this->showRoles($group);
                }
                if ($this->_options['users']) {
                        $this->showUsers($group);
                }
        }

        private function showDepartment($department = null)
        {
                if (!isset($department)) {
                        $department = new Department($this->_options['division'], $this->_options['department']);
                }

                $this->flash->success(sprintf("Department: %s", $department->getName()));

                if ($this->_options['exams']) {
                        $this->showExams($department);
                }
                if ($this->_options['roles']) {
                        $this->showRoles($department);
                }
                if ($this->_options['users']) {
                        $this->showUsers($department);
                }
        }

        private function showDivision($division = null)
        {
                if (!isset($division)) {
                        $division = new Division($this->_options['division']);
                }

                $this->flash->success(sprintf("Division: %s", $division->getName()));

                if ($this->_options['exams']) {
                        $this->showExams($division);
                }
                if ($this->_options['roles']) {
                        $this->showRoles($division);
                }
                if ($this->_options['users']) {
                        $this->showUsers($division);
                }
        }

        private function showDivisions()
        {
                $divisions = Division::getDivisions();
                foreach ($divisions as $division) {
                        $this->showDivision($division);
                }
        }

        /**
         * Show exams for organization unit.
         * @param OrganizationUnit $organization
         */
        private function showExams($organization)
        {
                $exams = $organization->getExams();
                $this->flash->success(sprintf("-> Exams (%d):", $exams->getSize()));

                if ($this->_options['verbose']) {
                        foreach ($exams->getData() as $data) {
                                $this->flash->success(sprintf("  -> Data: %s", json_encode($data)));
                        }
                }
        }

        /**
         * Show roles for organization unit.
         * @param OrganizationUnit $organization
         */
        private function showRoles($organization)
        {
                $roles = $organization->getRoles();

                $this->flash->success(sprintf("-> Roles (%d):", $roles->getSize()));

                if ($this->_options['verbose']) {
                        foreach ($roles->getData() as $data) {
                                $this->flash->success(sprintf("  -> Data: %s", json_encode($data)));
                        }
                }
        }

        /**
         * Show users for organization unit.
         * @param OrganizationUnit $organization
         */
        private function showUsers($organization)
        {
                $users = $organization->getUsers();

                $this->flash->success(sprintf("-> Users (%d):", $users->getSize()));
                $this->flash->success(sprintf("  -> Students  (%d):", $users->getStudents()->getSize()));
                $this->flash->success(sprintf("  -> Employees (%d):", $users->getEmployees()->getSize()));

                if ($this->_options['verbose']) {
                        foreach ($users->getData() as $data) {
                                $this->flash->success(sprintf("  -> Data: %s", json_encode($data)));
                        }
                }
        }

        /**
         * List all workgroups.
         */
        private function listWorkGroups()
        {
                $groups = WorkGroup::getGroups();
                foreach ($groups as $group) {
                        $this->flash->success(sprintf("%s -> %s -> %s", $group->getDivision(), $group->getDepartment(), $group->getName()));
                }
        }

        /**
         * List all departments.
         */
        private function listDepartments()
        {
                $departments = Department::getDepartments();
                foreach ($departments as $department) {
                        $this->flash->success(sprintf("%s -> %s", $department->getDivision(), $department->getName()));
                }
        }

        /**
         * List all divisions.
         */
        private function listDivisions()
        {
                $divisions = Division::getDivisions();
                foreach ($divisions as $division) {
                        $this->flash->success($division->getName());
                }
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'show', 'list', 'explore', 'summary', 'migrate', 'division', 'department', 'workgroup', 'exams', 'roles', 'users');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->_options[$option])) {
                                $this->_options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }
        }

}
