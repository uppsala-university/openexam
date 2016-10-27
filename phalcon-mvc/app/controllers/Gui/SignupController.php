<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
         * The teacher signup object.
         * @var Teacher
         */
        private $_teacher;
        /**
         * The student signup object.
         * @var Student
         */
        private $_student;
        /**
         * Should signup be enabled or not?
         * @var boolean
         */
        private $_enabled = false;

        public function initialize()
        {
                parent::initialize();

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

        public function indexAction()
        {
                if ($this->config->signup->wizard == false) {
                        $this->dispatcher->forward(array(
                                'action' => 'process'
                        ));
                        return false;
                }
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

        public function reloadAction()
        {
                $this->dispatcher->forward(array(
                        'action' => 'index'
                ));
                return false;
        }

        public function processAction()
        {
                if (!$this->setViewData()) {
                        $this->view->pick(array("signup/missing"));
                } else {
                        $this->view->setVar('wait', $this->url->get('/img/ui-anim_basic_16x16.gif'));
                        $this->view->setVar('done', $this->url->get('/img/tick-circle.png'));
                }
                if ($this->config->signup->wizard == false) {
                        $this->view->setVar('next', $this->url->get($this->config->session->startPage));
                        $this->view->setVar('wizard', false);
                } else {
                        $this->view->setVar('next', $this->url->get('/signup/finished'));
                        $this->view->setVar('wizard', true);
                }
        }

        public function finishedAction()
        {
                $this->view->setVar('startpage', $this->url->get($this->config->session->startPage));
        }

        public function removeAction()
        {
                $this->view->setVar('employee', $this->user->affiliation->isEmployee());
                $this->_teacher->remove();
        }

        private function setViewData()
        {
                if ($this->user->getUser() == null) {
                        $this->view->setVar('reload', $this->url->get('/signup/reload'));
                        return $this->_enabled = true;
                }

                if ($this->user->affiliation->isEmployee()) {
                        $this->_enabled = true;
                        $this->view->setVar('teacher', $this->_teacher);
                        $this->view->setVar('texams', Exam::find(
                                sprintf("id IN (%s)", implode(",", $this->_teacher->getExams()))
                        ));
                }
                if ($this->user->affiliation->isStudent()) {
                        $this->_enabled = true;
                        $this->view->setVar('student', $this->_student);
                        $this->view->setVar('sexams', Exam::find(
                                sprintf("id IN (%s)", implode(",", $this->_student->getExams()))
                        ));
                }

                return $this->_enabled;
        }

}
