<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    TaskController.php
// Created: 2017-02-13 22:39:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Security\Roles;

/**
 * Task controller.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TaskController extends GuiController
{

        /**
         * Exam manage task.
         * @param int $state The exam state.
         */
        public function manageAction($state = null)
        {
                $this->roleAction(array(
                        'state' => $state
                    ), Roles::CREATOR
                );
        }

        /**
         * Question contribute task.
         */
        public function contributeAction()
        {
                $this->roleAction(array(
                        'state' => State::CONTRIBUTABLE,
                        'order' => 'starttime',
                        'match' => array(
                                'published' => false
                        )
                    ), Roles::CONTRIBUTOR
                );
        }

        /**
         * Answer correction task.
         */
        public function correctAction()
        {
                $this->roleAction(array(
                        'state' => State::CORRECTABLE | State::CORRECTED,
                        'order' => 'starttime',
                        'match' => array(
                                'published' => true,
                                'decoded'   => false
                        )
                    ), Roles::CORRECTOR
                );
        }

        /**
         * Exam invigilation task.
         */
        public function invigilateAction()
        {
                $this->roleAction(array(
                        'state' => State::EXAMINATABLE,
                        'order' => 'starttime'
                    ), Roles::INVIGILATOR
                );
        }

        /**
         * Exam decode task.
         */
        public function decodeAction()
        {
                $this->roleAction(array(
                        'state' => State::DECODABLE,
                        'order' => 'starttime',
                        'match' => array(
                                'decoded' => false
                        )
                    ), Roles::DECODER
                );
        }

        /**
         * Student result task.
         */
        public function resultAction()
        {
                $this->roleAction(array(
                        'state' => State::DECODED,
                        'order' => 'starttime',
                        'match' => array(
                                'decoded' => true
                        )
                    ), Roles::STUDENT, 'student-finished'
                );
        }

        /**
         * Student exam task.
         */
        public function upcomingAction()
        {
                $this->roleAction(array(
                        'state' => State::UPCOMING | State::RUNNING,
                        'order' => 'starttime',
                        'match' => array(
                                'published' => true
                        )
                    ), Roles::STUDENT, 'student-upcoming'
                );
        }

        /**
         * Task helper method.
         * 
         * @param array $filter The filter options.
         * @param string $role The user role.
         * @param string $sect The exam section (defaults to role).
         */
        private function roleAction($filter, $role, $sect = null)
        {
                if (!isset($sect)) {
                        $sect = $role;
                }

                if (!isset($filter['search'])) {
                        $filter['search'] = '';
                }
                if (!isset($filter['order'])) {
                        $filter['order'] = 'id';
                }
                if (!isset($filter['sort'])) {
                        $filter['sort'] = 'desc';
                }
                if (!isset($filter['state'])) {
                        $filter['state'] = 0;
                }
                if (!isset($filter['match'])) {
                        $filter['match'] = array();
                }

                $this->view->setVars(array(
                        'state'  => array($sect => $filter['state']),
                        'roles'  => array($sect),
                        'expand' => array($sect),
                        'filter' => $filter
                ));

                $this->view->pick(array('exam/index'));
        }

}
