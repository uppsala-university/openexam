<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    SignupController.php
// Created: 2015-03-12 13:03:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Security\Signup\Student;
use OpenExam\Library\Security\Signup\Teacher;
use OpenExam\Models\Exam;

/**
 * System user signup controller.
 * 
 * Provides a signup service as system users (teacher and/or student) to 
 * logged on users. It has to be enabled in the system configuration before
 * being used.
 * 
 * Configuration also defines exams that should be made available for teachers 
 * and students: 
 * 
 * o) For employees, the list defines exams that is cloned to their user 
 *    account added as a teacher account. 
 * 
 * o) For students, the list defines exams which they are registered on as 
 *    students.
 * 
 * The classification of logged in persons as teacher/student are based on 
 * LDAP attributes. 
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SignupController extends GuiController
{

        /**
         * The teacher sign-up object.
         * @var Teacher
         */
        private $_teacher;
        /**
         * The student sign-up object.
         * @var Student
         */
        private $_student;
        /**
         * Should sign-up be enabled or not?
         * @var boolean
         */
        private $_enabled = false;

        public function initialize()
        {
                parent::initialize();

                $this->view->setTemplateBefore('cardbox');
                $this->view->setVar('icon', $this->url->get('img/tools-wizard.png'));

                $this->_teacher = new Teacher($this->user->getPrincipalName());
                $this->_student = new Student($this->user->getPrincipalName());

                if ($this->config->signup->_enabled == false) {
                        $this->view->pick(array("signup/disabled"));
                }

                if ($this->_student->isEnabled() == false &&
                    $this->_teacher->isEnabled() == false) {
                        $this->view->pick(array("signup/disabled"));
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_student);
                unset($this->_teacher);
        }

        /**
         * Start action for sign-up.
         * @return boolean
         */
        public function indexAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Check if signup should use wizard mode. Otherwise simply 
                // forward caller to process action.
                // 
                if ($this->config->signup->wizard == false) {
                        $this->dispatcher->forward(array(
                                'action' => 'process'
                        ));
                        return false;
                }

                // 
                // Pass data to view:
                // 
                if (!$this->setViewData()) {
                        $this->view->pick(array("signup/missing"));
                }
                if ($this->config->signup->automatic == false) {
                        $this->view->setVar('insert', $this->url->get('/signup/process'));
                }
                if ($this->config->signup->remove) {
                        $this->view->setVar('remove', $this->url->get('/signup/remove'));
                }
        }

        /**
         * Reload signup status.
         * @return boolean
         */
        public function reloadAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Forward to index action:
                // 
                $this->dispatcher->forward(array(
                        'action' => 'index'
                ));
                return false;
        }

        /**
         * Process sign-up action.
         */
        public function processAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Handle case if sign-up was disabled in system config:
                // 
                if (!$this->setViewData()) {
                        $this->view->pick(array("signup/missing"));
                } else {
                        $this->view->setVar('wait', $this->url->get('/img/ui-anim_basic_16x16.gif'));
                        $this->view->setVar('done', $this->url->get('/img/tick-circle.png'));
                }

                // 
                // Next mode for sign-up:
                // 
                if ($this->config->signup->wizard == false) {
                        $this->view->setVar('next', $this->url->get($this->config->session->startPage));
                        $this->view->setVar('wizard', false);
                } else {
                        $this->view->setVar('next', $this->url->get('/signup/finished'));
                        $this->view->setVar('wizard', true);
                }
        }

        /**
         * Sign-up finished action.
         */
        public function finishedAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Goto start page:
                // 
                $this->view->setVar('startpage', $this->url->get($this->config->session->startPage));
        }

        /**
         * Remove teacher role.
         */
        public function removeAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Is caller an employee?:
                // 
                $this->view->setVar('employee', $this->user->affiliation->isEmployee());
                $this->_teacher->remove();
        }

        /**
         * Set data for view.
         * @return boolean
         */
        private function setViewData()
        {
                // 
                // Reload if not logged in:
                // 
                if ($this->user->getUser() == null) {
                        $this->view->setVar('reload', $this->url->get('/signup/reload'));
                        return $this->_enabled = true;
                }

                // 
                // Caller is an employee:
                // 
                if ($this->user->affiliation->isEmployee()) {
                        $this->_enabled = true;
                        $this->view->setVar('teacher', $this->_teacher);
                        $this->view->setVar('texams', Exam::find(
                                sprintf("id IN (%s)", implode(",", $this->_teacher->getExams()))
                        ));
                }

                // 
                // Caller is a student:
                // 
                if ($this->user->affiliation->isStudent()) {
                        $this->_enabled = true;
                        $this->view->setVar('student', $this->_student);
                        $this->view->setVar('sexams', Exam::find(
                                sprintf("id IN (%s)", implode(",", $this->_student->getExams()))
                        ));
                }

                // 
                // Return status whether continue:
                // 
                return $this->_enabled;
        }

}
