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
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Core\Exam\Student\Access;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Answer;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Student;
use Phalcon\Mvc\View;

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
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

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
                        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
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
                // 
                // Initialization:
                // 
                $questData = $ansData = array();
                $exam = false;

                // 
                // Get sanitized request parameters:
                // 
                $examId = $this->dispatcher->getParam("examId", "int");
                $questId = $this->dispatcher->getParam("questId", "int");

                // 
                // Load exam object using primary role (if any). If primary 
                // role is unset, then use roles allowed to access this action.
                // 
                // TODO: Keep access list of allowed roles in parent controller.
                // 
                if ($this->user->hasPrimaryRole()) {
                        $exam = Exam::findFirst($examId);
                }
                if (!$exam) {
                        $role = $this->user->setPrimaryRole(Roles::STUDENT);
                        $exam = Exam::findFirst($examId);
                }
                if (!$exam) {
                        $role = $this->user->setPrimaryRole(Roles::CREATOR);
                        $exam = Exam::findFirst($examId);
                }
                if (!$exam) {
                        throw new \Exception("Failed to load exam for this question");
                }

                // 
                // Is the exam accessed in test mode?:
                // 
                if ($exam->creator == $this->user->getPrincipalName()) {
                        $exam->testcase = true;
                        $this->user->setPrimaryRole(Roles::CREATOR);
                }

                if (!$exam->testcase) {
                        // 
                        // Check exam access:
                        // 
                        if ($exam->lockdown->enable) {
                                $access = new Access($exam);
                                switch ($access->open()) {
                                        case Access::OPEN_APPROVED;
                                                $this->logger->access->debug("Approved exam access for student");
                                                break;
                                        case Access::OPEN_DENIED:
                                                throw new \Exception("Access denied for exam", Error::FORBIDDEN);
                                        case Access::OPEN_PENDING:
                                                $this->dispatcher->forward(array(
                                                        'controller' => 'exam',
                                                        'action'     => 'pending',
                                                        'params'     => array('exam' => $exam)
                                                ));
                                                return false;
                                }
                        }

                        // 
                        // Disable further access control:
                        // 
                        $this->user->setPrimaryRole(Roles::SYSTEM);

                        // 
                        // Find student object of this logged in person for this exam:
                        // 
                        if (!($student = Student::findFirst("user = '" . $this->user->getPrincipalName() . "' and exam_id = " . $examId))) {
                                throw new \Exception("You are not authorized to access this question");
                        }

                        // 
                        // Redirect to index page if exam is not running:
                        // 
                        if ($exam->getState()->has(State::RUNNING) == false) {
                                return $this->response->redirect('exam/index');
                        }
                }

                ## load all questions in this exam for highlighting questions
                $allQs = $exam->getQuestions(array('order' => 'slot'));
                $firtstQ = $allQs->getFirst();

                ## check if needed to load a specific question
                if ($questId) {
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
                        if (!$exam->testcase) {
                                $ans = Answer::findFirst("student_id = " . $student->id . " and question_id = " . $questData[0]->id);
                                if (!$ans) {
                                        //lets add answer record in database now
                                        $ans = new Answer();
                                        $ans->save(array(
                                                'student_id'  => $student->id,
                                                'question_id' => $questData[0]->id,
                                                'answered'    => 0
                                        ));
                                }
                                // to array, doing so to keeps things clean in view
                                $ansData[$questData[0]->id] = $ans;
                        }
                } else {
                        $viewMode = 'all';
                        $questData = $allQs;

                        ## load all answers that logged in student has given against all qs
                        if (!$exam->testcase) {
                                foreach ($allQs as $qObj) {
                                        $tmp = Answer::findFirst("student_id = " . $student->id . " and question_id = " . $qObj->id);
                                        if (is_object($tmp) && $tmp->count()) {
                                                $ansData[$qObj->id] = $tmp;
                                        }
                                }
                        }
                }

                ## get list of all questions that this student has asked to highlight
                $highlightedQuestList = array();
                if (!$exam->testcase) {
                        foreach ($allQs as $q) {
                                $allAns = $q->getAnswers('student_id = ' . $student->id);
                                if (is_object($allAns) && $allAns->count()) {
                                        foreach ($allAns as $stAns) {
                                                $stAnsData = json_decode($stAns->answer, true);
                                                if (isset($stAnsData['highlight-q']) && $stAnsData['highlight-q'] == 'yes') {
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
                        'testMode'      => $exam->testcase,
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
                $examId = $this->filter->sanitize($this->dispatcher->getParam("examId"), "int");

                ## load exam data
                $exam = Exam::findFirst($examId);
                if (!$exam) {
                        throw new \Exception("Unable to load requested exam");
                }

                ## find display mode
                preg_match('/^\/([a-z]+)\/([0-9]+)/', $loadAnswersBy, $loadBy);

                $isCreator = $this->user->roles->aquire(Roles::CREATOR, $examId);
                $isDecoder = $this->user->roles->aquire(Roles::DECODER, $examId);

                if (count($loadBy)) {

                        switch ($loadBy[1]) {
                                case 'student':

                                        // show all answers for a specific student
                                        $questData = $this->modelsManager->executeQuery(
                                            "select distinct q.* from OpenExam\Models\Question q "
                                            . "inner join OpenExam\Models\Corrector c "
                                            . "where  exam_id = '" . $exam->id . "' and q.status = 'active' "
                                            . ((!$isDecoder && $exam->creator != $this->user->getPrincipalName()) ?
                                                "and c.user = '" . $this->user->getPrincipalName() . "' " : " ")
                                            . "order by q.slot asc"
                                        );
                                        $stData = Student::findFirst($loadBy[2]);
                                        $ansData = Answer::find('student_id = ' . $loadBy[2]);
                                        $heading = 'Student (ID: ' . $stData->id . ')';

                                        break;

                                case 'question':

                                        // show student answers for a specific question
                                        $questData = $this->modelsManager->executeQuery(
                                            "select distinct q.* from OpenExam\Models\Question q "
                                            . "inner join OpenExam\Models\Corrector c "
                                            . "where q.id = " . $loadBy[2] . " and q.status = 'active' "
                                            . ((!$isDecoder && $isCreator) ?
                                                "and c.user = '" . $this->user->getPrincipalName() . "' " : " ")
                                        );
                                        $ansData = Answer::find('question_id = ' . $loadBy[2]);
                                        $heading = 'Question no. ' . $questData[0]->slot ? $questData[0]->slot : $questData[0]->name;
                                        break;

                                case 'answer':

                                        // show a specific answer
                                        $questData = $this->modelsManager->executeQuery(
                                            "select distinct q.* from OpenExam\Models\Question q "
                                            . "inner join OpenExam\Models\Corrector c "
                                            . "inner join OpenExam\Models\Answer a "
                                            . "where a.id = " . $loadBy[2] . " and q.status = 'active' " 
                                            . ((!$isDecoder && $isCreator) ?
                                                "and c.user = '" . $this->user->getPrincipalName() . "' " : " ")
                                        );
                                        $ansData = Answer::find('id = ' . $loadBy[2]);
                                        $stData = Student::findFirst($ansData[0]->student_id);
                                        $heading = 'Question no. ' . $questData[0]->slot ? $questData[0]->slot : $questData[0]->name . ', '
                                            . ' answered by Student (ID: ' . $stData->id . ')';

                                        break;

                                default:
                                        throw new \Exception("Unable to load answers for provided criteria");
                                        break;
                        }

                        $this->view->setVars(array(
                                'loadBy'  => $loadBy[1],
                                'answers' => $ansData,
                                'heading' => $heading
                        ));

                        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                        $this->view->pick('question/answers');
                } else {
                        // we will show score board
                        if (!$exam->decoded) {
                                $questData = $this->modelsManager->executeQuery(
                                    "select distinct q.* from OpenExam\Models\Question q "
                                    . "inner join OpenExam\Models\Corrector c "
                                    . "where exam_id = '" . $exam->id . "' and q.status = 'active' "
                                    . ($isDecoder || $isCreator ? " " : "and c.user = '" . $this->user->getPrincipalName() . "' ")
                                    . "order by q.slot asc"
                                );
                        } else {
                                $questData = $exam->getQuestions(array('order' => 'slot asc', 'conditions' => "status = 'active'"));
                        }


                        $studentData = $this->modelsManager->executeQuery(
                            "select distinct s.* from OpenExam\Models\Student s "
                            . "inner join OpenExam\Models\Answer a on a.student_id = s.id "
                            . "where s.exam_id = '" . $exam->id . "'"
                            . "order by s.code asc"
                        );

                        $this->view->setVars(array(
                                'students' => $studentData
                        ));
                }


                $this->view->setVars(array(
                        'exam'      => $exam,
                        'questions' => $questData
                ));
        }

}
