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


        /**
         * Question view for student and for test exam
         * For students and for exam manager
         * 
         * exam/{exam_id}/question/{question_id}?
         */
        public function viewAction()
        {
                $loggedIn = $this->user->getPrincipalName();
                
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
                $student = Student::findFirst("user = '" . $loggedIn ."' and exam_id=" . $examId);
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
                
                ## get list of all questions that this student has asked to highlight
                $highlightedQuestList = array();
                $allQs = $exam->getQuestions();
                foreach ($allQs as $q) {
                        $allAns = $q->getAnswers('student_id = '.$student->id);
                        if(is_object($allAns) && $allAns->count()) {
                                foreach($allAns as $stAns) {
                                        $stAnsData = json_decode($stAns->answer, true);
                                        if(isset($stAnsData['highlight-q']) && $stAnsData['highlight-q'] == 'yes') {
                                                $highlightedQuestList[] = $stAns->question_id;
                                        }
                                }
                        }
                }
                
                $this->view->setVars(array(
                        'exam'          => $exam,
                        'quest'         => $quest,
                        'answer'        => $ans,
                        'highlightedQs' => $highlightedQuestList
                ));
                $this->view->setLayout('thin-layout');
        }

        /**
         * Question correction
         * Allows correctors to check student answers in exam
         * 
         * exam/{exam_id}/correction/{correction-by}/{question_id}
         */
        public function correctionAction($examId, $loadAnswersBy = NULL)
        {
                ## sanitize
                $examId  = $this->filter->sanitize($this->dispatcher->getParam("examId"), "int");

                ## load exam data
                $exam = Exam::findFirst($examId);
                if (!$exam) {
                        throw new Exception("Unable to load requested exam");
                }
                
                ## find display mode
                preg_match('/^\/([a-z]+)\/([0-9]+)/', $loadAnswersBy, $loadBy);
                if(count($loadBy)) {
                        
                        switch($loadBy[1])
                        {
                                case 'student':
                                        
                                        // show all answers for a specific student
                                        $questData = $exam->getQuestions();
                                        $stData = Student::findFirst($loadBy[2]);
                                        $ansData = Answer::find('student_id = '.$loadBy[2]);
                                        $heading = 'Student (code: '.$stData->code.')';
                                        
                                        break;

                                case 'question':
                                        
                                        // show student answers for a specific question
                                        $questData = Question::find('id = ' . $loadBy[2]);
                                        $ansData = Answer::find('question_id = '.$loadBy[2]);
                                        $heading = 'Question no. '.$questData[0]->name;
                                        break;
                                
                                case 'answer':
                                        
                                        // show a specific answer
                                        $questData = $this->phql->executeQuery(
                                                        "select q.* from OpenExam\Models\Question q "
                                                        .   "inner join OpenExam\Models\Answer a "
                                                        .   "where a.id = ".$loadBy[2]
                                                );
                                        $ansData = Answer::find('id = ' . $loadBy[2]);
                                        $stData  = Student::findFirst($ansData->student_id);
                                        $heading = 'Question no. '. $questData[0]->name .', '
                                            . ' answered by Student (code: '.$stData->code.')';
                                        
                                        break;
                                
                                default:
                                        throw new \Exception("Unable to load answers for provided criteria");
                                        break;
                        }
                        
                        $this->view->setVars(array(
                                'loadBy'        => $loadBy[1],
                                'exam'          => $exam,
                                'questions'     => $questData,
                                'answers'       => $ansData,
                                'heading'       => $heading
                            
                        ));
                        
                        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
                        $this->view->pick('question/answers');
                        
                } else {
                        // we will show score board
                        
                        
                }
                
                
                $this->view->setVar('exam', $exam);
        }
        
}
