<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
