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
use OpenExam\Models\Student;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Answer;

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
                $loggedIn = $this->session->get('authenticated');
                
                ## sanitize
                $examId  = $this->filter->sanitize($this->dispatcher->getParam("examId"), "int");
                $questId = $this->filter->sanitize($this->dispatcher->getParam("questId"), "int");
                
                ## load exam with time checking
                $exam = Exam::findFirst("id = " . $examId . " and starttime <= NOW() and endtime > NOW()");
                if (!$exam) {
                        return $this->response->redirect('exam/index');
                }
                
                ## pass data to view and load
                $quest = Question::findFirst("name = '" . $questId ."' and exam_id=" . $examId);
                if(!$quest) {
                        // load first question if requested question don't exist
                        $quest = Question::findFirst(array(
                                'exam_id=' . $examId,
                                'order' => 'name asc'
                        ));
                }
                
                ## create an entry in answer table for this question against this student
                // first, find student id of this logged in person for this exam
                $student = Student::findFirst("user = '" . $loggedIn['user'] ."' and exam_id=" . $examId);
                if(!$student) {
                        throw new \Exception("You are not authorized to access this question");
                }
                

                ## pick up answer data if student has answered
                $ans = Answer::findFirst("student_id = " . $student->id ." and question_id = " . $quest->id);
                if(!$ans) {
                        //lets add answer record in database now
                        $ans = new Answer();
                        $ans->save(array(
                                'student_id' => $student->id,
                                'question_id'=> $quest->id,
                                'answered'   => 'N'
                        ));
                }
                
                
                $this->view->setVars(array(
                        'exam'          => $exam,
                        'quest'         => $quest,
                        'answer'        => $ans
                ));
                $this->view->setLayout('thin-layout');
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
