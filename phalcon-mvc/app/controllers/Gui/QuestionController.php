<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    QuestionController.php
// Created: 2014-09-29 12:49:30
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Models\Question;

/**
 * Controller for adding/loading Exam questions
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class QuestionController extends GuiController
{

        /**
         * Question view for student and for test exam
         * For students and for exam manager
         * 
         * exam/{exam_id}/question/{question_id}?
         */
        public function viewAction()
        {

                $this->view->setLayout('thin-layout');

                $examId = $this->dispatcher->getParam("examId");
                $questId = $this->dispatcher->getParam("questId");
                //print $examId . "--" . $questId;

                // show exam instructions page if no quesiton id provided and
                // user has not started his exam yet. Redirect to exam/view, otherwise
                // fetch exam instructions for this exam and show
                // if student, check if he has permissions to view this exam
                // and the time has been started for this exam
                // Show instructions page if exam has not been started yet
                // otherwise, show exam 
        }

        /**
         * Add question view
         * question/create
         */
        public function createAction()
        {
                //disable main layout
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

                //pickup question form
                $this->view->pick("question/form");
        }

        /**
         * Update question view
         * question/update
         */
        public function updateAction()
        {

                if ($this->request->hasPost('q_id')) {

                        // sanitize
                        $quesId = $this->filter->sanitize($this->request->getPost('q_id'), "int");

                        // fetch data
                        $question = Question::findFirst($quesId);

                        //load question form with preloaded data and layout disabled
                        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
                        $this->view->setVar('question', $question);
                        $this->view->pick("question/form");
                } else {

                        throw new \Exception('Invalid data provided');
                }
        }

}
