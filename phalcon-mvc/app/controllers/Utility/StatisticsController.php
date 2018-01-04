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
// File:    StatisticsController.php
// Created: 2016-04-28 17:57:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Statistics;
use OpenExam\Library\Security\Exception as SecurityException;

/**
 * Statistics (system usage) controller.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class StatisticsController extends GuiController
{

        public function initialize()
        {
                parent::initialize();
                $this->view->setTemplateBefore('cardbox');
        }

        /**
         * View action.
         */
        public function indexAction()
        {
                $this->checkAccess();
        }

        /**
         * Send summary data (count of exams, roles and users).
         * @param string $division The optional division name.
         */
        public function summaryAction($division = null)
        {
                $this->checkAccess();

                $statistics = new Statistics($division);

                $content = array(
                        'name' => $statistics->getName(),
                        'data' => $statistics->getSummary()
                );

                unset($statistics);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send organization data.
         * @param string $division The optional division name.
         */
        public function organizationAction($division = null)
        {
                $this->checkAccess();

                $statistics = new Statistics($division);

                $content = array(
                        'name'     => $statistics->getName(),
                        'data'     => $statistics->getData(),
                        'children' => array()
                );

                foreach ($statistics->getChildren() as $child) {
                        $content['children'][] = $child->getData();
                        unset($child);
                }

                unset($statistics);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send user data for role.
         * @param string $role The role name.
         * @param string $division The optional division name.
         */
        public function roleAction($role, $division = null)
        {
                $this->checkAccess();

                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }

                $statistics = new Statistics($division);
                $users = $statistics->getRole($role);
                $users->addDecoration();

                $content = array(
                        'size' => $users->getSize(),
                        'data' => $users->getData(),
                        'name' => $users->getName()
                );

                unset($statistics);
                unset($users);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send exams data.
         * @param string $division The optional division name.
         */
        public function examsAction($division = null)
        {
                $this->checkAccess();

                $statistics = new Statistics($division);
                $exams = $statistics->getExams();

                $content = array(
                        'size' => $exams->getSize(),
                        'data' => $exams->getData()
                );

                unset($statistics);
                unset($exams);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send users data.
         * @param string $division The optional division name.
         */
        public function usersAction($division = null)
        {
                $this->checkAccess();

                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }

                $statistics = new Statistics($division);
                $users = $statistics->getUsers();
                $users->addDecoration();

                $content = array(
                        'size' => $users->getSize(),
                        'data' => $users->getData(),
                        'name' => $users->getName()
                );

                unset($statistics);
                unset($users);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send employees data.
         * @param string $division The optional division name.
         */
        public function employeesAction($division = null)
        {
                $this->checkAccess();

                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }

                $statistics = new Statistics($division);
                $users = $statistics->getUsers()->getEmployees();
                $users->getProvider()->addDecoration();

                $content = array(
                        'size' => $users->getSize(),
                        'data' => $users->getData(),
                        'name' => $users->getName()
                );

                unset($statistics);
                unset($users);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Send student data.
         * @param string $division The optional division name.
         */
        public function studentsAction($division = null)
        {
                $this->checkAccess();

                if ($this->user->affiliation->isEmployee() == false) {
                        throw new SecurityException("Only available for employees", Error::FORBIDDEN);
                }

                $statistics = new Statistics($division);
                $users = $statistics->getUsers()->getStudents();
                $users->getProvider()->addDecoration();

                $content = array(
                        'size' => $users->getSize(),
                        'data' => $users->getData(),
                        'name' => $users->getName()
                );

                unset($statistics);
                unset($users);

                $this->view->disable();
                $this->response->setJsonContent($content);
                $this->response->send();
        }

}
