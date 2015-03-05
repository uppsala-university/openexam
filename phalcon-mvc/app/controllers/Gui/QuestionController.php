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
         * 
         * Allowed to Roles: creator, contributor
         */
        public function createAction()
        {
                // sanitize
                $examId = $this->filter->sanitize($this->request->getPost('exam_id'), "int");

                $this->view->setVar('exam', Exam::findFirst($examId));
                
                //disable main layout
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

                //pickup question form
                $this->view->pick("question/form");
        }

        /**
         * Update question view
         * question/update
         * 
         * Allowed to Roles: creator, contributor
         */
        public function updateAction()
        {

                if ($this->request->hasPost('q_id')) {

                        // sanitize
                        $quesId = $this->filter->sanitize($this->request->getPost('q_id'), "int");

                        // fetch data
                        $question = Question::findFirst($quesId);
                        $exam = Exam::findFirst($question->exam_id);
                            
                        //load question form with preloaded data and layout disabled
                        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
                        $this->view->setVars(array(
                                'question' => $question,
                                'exam'     => $exam
                        ));
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
         * 
         * Allowed to Roles: creator, students
         */
        public function viewAction()
        {
                //initializations
                $questData = $ansData = array();
                $student = NULL;
                $loggedIn = $this->user->getPrincipalName();
                
                ## sanitize
                $examId  = $this->filter->sanitize($this->dispatcher->getParam("examId"), "int");
                $questId = $this->filter->sanitize($this->dispatcher->getParam("questId"), "int");

                ## load exam
                $exam = Exam::findFirst($examId);
                
                ## if it is exam test mode?
                $testMode = FALSE;
                if($exam->creator == $loggedIn) {
                        $testMode = TRUE;
                } else {
                        
                        ## find student id of this logged in person for this exam
                        $student = Student::findFirst("user = '" . $loggedIn ."' and exam_id = " . $examId);
                        if(!$student) {
                                throw new \Exception("You are not authorized to access this question");
                        }

                        $examStartsAt 	= !is_null($student->starttime) ? $student->starttime : $exam->starttime;
                        $examEndsAt 	= !is_null($student->endtime) ? $student->endtime : $exam->endtime;
                        
                        ## load exam with time checking
                        if (strtotime($examStartsAt) > strtotime("now") || strtotime($examEndsAt) < strtotime("now")) {
                                return $this->response->redirect('exam/index');
                        }
                }        
                
                ## load all questions in this exam for highlighting questions
                $allQs = $exam->getQuestions(array('order'=>'slot'));
                $firtstQ = $allQs->getFirst();
                
                ## check if needed to load a specific question
                if($questId) {
                        $viewMode = 'single';
                        
                        ## load question data
                        $quest = $allQs->filter(function($qObj) use ($questId) {
                            if ($qObj->id == $questId) {
                                    return $qObj;
                            }
                        });
                        
                        // to array, doing so to keeps things clean in view
                        $questData[0] = (!$quest ? $firtstQ : $quest[0]);
                        
                        ## pick up answer data if student has answered
                        ## otherwise, create an entry in answer table for this question against this student
                        if(!$testMode) {
                                $ans = Answer::findFirst("student_id = " . $student->id ." and question_id = " . $questData[0]->id);
                                if(!$ans) {
                                        //lets add answer record in database now
                                        $ans = new Answer();
                                        $ans->save(array(
                                                'student_id' => $student->id,
                                                'question_id'=> $questData[0]->id,
                                                'answered'   => 0
                                        ));
                                }
                                // to array, doing so to keeps things clean in view
                                $ansData[$questData[0]->id]   = $ans;                                
                        }
                        
                } else {
                        $viewMode = 'all';
                        $questData = $allQs;
                        
                        ## load all answers that logged in student has given against all qs
                        if(!$testMode) {
                                foreach ($allQs as $qObj) {
                                        $tmp = Answer::findFirst("student_id = " . $student->id ." and question_id = " . $qObj->id);
                                        if(is_object($tmp) && $tmp->count()) {
                                                $ansData[$qObj->id] = $tmp;
                                        }
                                }
                        }        
                }
                
                ## get list of all questions that this student has asked to highlight
                $highlightedQuestList = array();
                if(!$testMode) {
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
                }
                
                $this->view->setVars(array(
                        'exam'          => $exam,
                        'questions'     => $allQs,
                        'quest'         => $questData,
                        'answer'        => $ansData,
                        'highlightedQs' => $highlightedQuestList,
                        'viewMode'      => $viewMode,
                        'testMode'      => $testMode,
                        'student'       => $student
                ));
                $this->view->setLayout('thin-layout');
        }

        /**
         * Question correction
         * Allows correctors to check student answers in exam
         * 
         * exam/{exam_id}/correction/{correction-by}/{question_id}
         * 
         * Allowed to Roles: correctors, decoder
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
                
                $isDecoder = $this->user->aquire(array('decoder'), $examId); 
                
                if(count($loadBy)) {
                        
                        switch($loadBy[1])
                        {
                                case 'student':
                                        
                                        // show all answers for a specific student
                                        $questData = $this->phql->executeQuery(
                                                        "select distinct q.* from OpenExam\Models\Question q "
                                                        .   "inner join OpenExam\Models\Corrector c "
                                                        .   "where  exam_id = '".$exam->id."' "
                                                        .   ((!$isDecoder && $exam->creator != $this->user->getPrincipalName()) ? 
                                                                "and c.user = '".$this->user->getPrincipalName()."' " : " ")
                                                        .   "order by q.slot asc"
                                                );
                                        $stData = Student::findFirst($loadBy[2]);
                                        $ansData = Answer::find('student_id = '.$loadBy[2]);
                                        $heading = 'Student (code: '.$stData->code.')';
                                        
                                        break;

                                case 'question':
                                        
                                        // show student answers for a specific question
                                        $questData = $this->phql->executeQuery(
                                                        "select distinct q.* from OpenExam\Models\Question q "
                                                        .   "inner join OpenExam\Models\Corrector c "
                                                        .   "where q.id = ".$loadBy[2]
                                                        .   ((!$isDecoder && $exam->creator != $this->user->getPrincipalName()) ? 
                                                                "and c.user = '".$this->user->getPrincipalName()."' " : " ")
                                                );
                                        $ansData = Answer::find('question_id = '.$loadBy[2]);
                                        $heading = 'Question no. '.$questData[0]->name;
                                        break;
                                
                                case 'answer':
                                        
                                        // show a specific answer
                                        $questData = $this->phql->executeQuery(
                                                        "select distinct q.* from OpenExam\Models\Question q "
                                                        .   "inner join OpenExam\Models\Corrector c "
                                                        .   "inner join OpenExam\Models\Answer a "
                                                        .   "where a.id = ".$loadBy[2]
                                                        .   ((!$isDecoder && $exam->creator != $this->user->getPrincipalName()) ? 
                                                                "and c.user = '".$this->user->getPrincipalName()."' " : " ")
                                                );
                                        $ansData = Answer::find('id = ' . $loadBy[2]);
                                        $stData  = Student::findFirst($ansData[0]->student_id);
                                        $heading = 'Question no. '. $questData[0]->name .', '
                                            . ' answered by Student (code: '.$stData->code.')';
                                        
                                        break;
                                
                                default:
                                        throw new \Exception("Unable to load answers for provided criteria");
                                        break;
                        }
                        
                        $this->view->setVars(array(
                                'loadBy'        => $loadBy[1],
                                'answers'       => $ansData,
                                'heading'       => $heading
                        ));
                        
                        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
                        $this->view->pick('question/answers');
                        
                } else {
                        
                        $isDecoder = $this->user->aquire(array('decoder'), $examId); 
                        
                        // we will show score board
                        if(!$exam->decoded) {
                                $questData = $this->phql->executeQuery(
                                        "select distinct q.* from OpenExam\Models\Question q "
                                        .   "inner join OpenExam\Models\Corrector c "
                                        .   "where exam_id = '".$exam->id."' "
                                        .   ((!$isDecoder && $exam->creator != $this->user->getPrincipalName()) ?
                                               "and c.user = '".$this->user->getPrincipalName()."' " : " ")
                                        .   "order by q.slot asc"
                                );
                        } else {
                                $questData = $exam->getQuestions(array('order'=>'slot asc'));
                        }        
                }
                
                
                $this->view->setVars(array(
                        'exam' => $exam,
                        'questions' => $questData
                    ));
        }
        
}
