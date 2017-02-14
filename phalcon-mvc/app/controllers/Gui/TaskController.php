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
use OpenExam\Models\Exam;

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
                $this->user->setPrimaryRole(Roles::CREATOR);
                $this->roleAction(Roles::CREATOR, Exam::find(), $state);
        }

        /**
         * Question contribute task.
         */
        public function contributeAction()
        {
                $this->user->setPrimaryRole(Roles::CONTRIBUTOR);
                $this->roleAction(Roles::CONTRIBUTOR, Exam::find(array(
                            'conditions' => "published = 'N'",
                            'order'      => 'starttime DESC'
                    )), State::CONTRIBUTABLE
                );
        }

        /**
         * Answer correction task.
         */
        public function correctAction()
        {
                $this->user->setPrimaryRole(Roles::CORRECTOR);
                $this->roleAction(Roles::CORRECTOR, Exam::find(array(
                            'conditions' => "published = 'Y' AND decoded = 'N'",
                            'order'      => 'starttime DESC'
                    )), function($exam) {
                        if ($exam->state & State::CORRECTABLE ||
                            $exam->state & State::CORRECTED) {
                                return $exam;
                        }
                });
        }

        /**
         * Exam invigilation task.
         */
        public function invigilateAction()
        {
                $this->user->setPrimaryRole(Roles::INVIGILATOR);
                $this->roleAction(Roles::INVIGILATOR, Exam::find(array(
                            'order' => 'starttime DESC'
                    )), State::EXAMINATABLE
                );
        }

        /**
         * Exam decode task.
         */
        public function decodeAction()
        {
                $this->user->setPrimaryRole(Roles::DECODER);
                $this->roleAction(Roles::DECODER, Exam::find(array(
                            'conditions' => "decoded = 'N'",
                            'order'      => 'starttime DESC'
                    )), State::DECODABLE
                );
        }

        /**
         * Student result task.
         */
        public function resultAction()
        {
                $this->user->setPrimaryRole(Roles::STUDENT);
                $this->roleAction('student-finished', Exam::find(array(
                            'conditions' => "decoded = 'Y'",
                            'order'      => 'starttime DESC'
                    ))
                );
        }

        /**
         * Task helper method.
         * 
         * @param string $role The task role.
         * @param Exam[] $exams The exams resultset.
         * @param int|callable $filter Optional resultset filter.
         */
        private function roleAction($role, $exams, $filter = null)
        {
                if (is_callable($filter)) {
                        $exams = $exams->filter($filter);
                } elseif (is_int($filter)) {
                        $exams = $exams->filter(function($exam) use($filter) {
                                if ($exam->state & $filter) {
                                        return $exam;
                                }
                        });
                }

                $this->view->setVars(array(
                        'roleBasedExamList' => array($role => $exams),
                        'expandExamTabs'    => array($role)
                ));
                $this->view->pick(array('exam/index'));
        }

}
