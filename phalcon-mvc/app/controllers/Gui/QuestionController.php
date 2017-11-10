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

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\Staff;
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
                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->getPost('exam_id', "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($role = $this->request->getPost('role', "string"))) {
                        throw new Exception("Missing required role", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Insert empty question:
                // 
                $question = new Question();
                $question->assign(array(
                        'exam_id'  => $exam->id,
                        'score'    => 0,
                        'quest'    => '{}',
                        'topic_id' => $exam->topics[0]->id,
                        'created'  => true
                ));

                if (!$question->create()) {
                        throw new Exception($question->getMessages()[0], Error::PRECONDITION_FAILED);
                } else {
                        $question->created = true;
                }

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'question' => $question,
                        'exam'     => $exam,
                        'role'     => $role,
                        'staff'    => new Staff($exam)
                ));

                // 
                // Disable main layout:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Pickup question form (non-standard path):
                // 
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
                // 
                // Sanitize:
                // 
                if (!($qid = $this->request->getPost('q_id', "int"))) {
                        throw new Exception("Missing or invalid question ID", Error::PRECONDITION_FAILED);
                }
                if (!($role = $this->request->getPost('role', "string"))) {
                        throw new Exception("Missing required role", Error::PRECONDITION_FAILED);
                }

                // 
                // Fetch data for view:
                // 
                if (!($question = Question::findFirst($qid))) {
                        throw new Exception("Failed fetch question model", Error::BAD_REQUEST);
                }
                if (!($exam = Exam::findFirst($question->exam_id))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Show view with main layout disabled:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                $this->view->setVars(array(
                        'question' => $question,
                        'exam'     => $exam,
                        'role'     => $role,
                        'staff'    => new Staff($exam)
                ));

                // 
                // Pickup question form (non-standard path):
                // 
                $this->view->pick("question/form");
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
                $questions = $answers = array();
                $exam = false;

                // 
                // Get sanitized request parameters:
                // 
                $eid = $this->dispatcher->getParam("examId", "int");
                $qid = $this->dispatcher->getParam("questId", "int");

                // 
                // Load exam object using primary role (if any). If primary 
                // role is unset, then use roles allowed to access this action.
                // 
                // TODO: Keep access list of allowed roles in parent controller.
                // 
                if ($this->user->hasPrimaryRole()) {
                        $exam = Exam::findFirst($eid);
                }

                // 
                // Using corrector instead of contributor might be a better option.
                // 
                if (!$exam) {
                        $role = $this->user->setPrimaryRole(Roles::CREATOR);
                        $exam = Exam::findFirst($eid);
                }
                if (!$exam) {
                        $role = $this->user->setPrimaryRole(Roles::CONTRIBUTOR);
                        $exam = Exam::findFirst($eid);
                }
                if (!$exam) {
                        $role = $this->user->setPrimaryRole(Roles::STUDENT);
                        $exam = Exam::findFirst($eid);
                }
                if (!$exam) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Is the exam accessed in test mode?:
                // 
                if ($this->user->getPrimaryRole() != Roles::STUDENT) {
                        $exam->testcase = true;
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
                                                throw new Exception("Access denied for exam", Error::FORBIDDEN);
                                        case Access::OPEN_PENDING:
                                                $this->dispatcher->forward(array(
                                                        'controller' => 'exam',
                                                        'action'     => 'pending',
                                                        'params'     => array('exam' => $exam)
                                                ));
                                                return false;
                                }

                                unset($access);
                        }

                        // 
                        // Disable further access control:
                        // 
                        $this->user->setPrimaryRole(Roles::SYSTEM);

                        // 
                        // Find student object of this logged in person for this exam:
                        // 
                        if (!($student = Student::findFirst(array(
                                    'conditions' => 'user = :user: AND exam_id = :exam:',
                                    'bind'       => array(
                                            'user' => $this->user->getPrincipalName(),
                                            'exam' => $eid
                                )))
                            )) {
                                throw new Exception("You are not authorized to access this question", Error::FORBIDDEN);
                        }

                        // 
                        // Redirect to index page if exam is not running:
                        // 
                        if ($exam->getState()->has(State::RUNNING) == false) {
                                return $this->response->redirect('exam/index');
                        }
                }

                // 
                // Load all questions in this exam for highlighting questions.
                // 
                $allQs = $exam->getQuestions(array(
                        'conditions' => "status = 'active'",
                        'order'      => 'slot'
                ));
                $firtstQ = $allQs->getFirst();

                if (!$firtstQ) {
                        // 
                        // This exam has no questions.
                        // 
                        $viewMode = 'none';
                } elseif ($qid) {
                        $viewMode = 'single';

                        //
                        // Load question data:
                        // 
                        $quest = $allQs->filter(function($qObj) use ($qid) {
                                if ($qObj->id == $qid) {
                                        return $qObj;
                                }
                        });

                        // 
                        // To array, doing so to keeps things clean in view.
                        // 
                        $questions[0] = (!$quest ? $firtstQ : $quest[0]);

                        //
                        // Pick up answer data if student has answered. Otherwise, create an entry 
                        // in answer table for this question against this student.
                        // 
                        if (!$exam->testcase) {
                                if (!($answer = Answer::findFirst(array(
                                            'conditions' => 'student_id = :sid: AND question_id = :qid:',
                                            'bind'       => array(
                                                    'sid' => $student->id,
                                                    'qid' => $questions[0]->id
                                            ))
                                    ))) {
                                        // 
                                        // Insert empty answer:
                                        // 
                                        $answer = new Answer();
                                        if (!$answer->save(array(
                                                    'student_id'  => $student->id,
                                                    'question_id' => $questions[0]->id,
                                                    'answered'    => 0
                                            ))) {
                                                throw new Exception(sprintf("Failed insert empty answer (%s)", $answer->getMessages()[0]));
                                        }
                                }

                                // 
                                // Insert answer model:
                                // 
                                $answers[$questions[0]->id] = $answer;
                        }

                        unset($quest);
                        unset($answer);
                } else {
                        $viewMode = 'all';
                        $questions = $allQs;

                        //
                        // Load all answers that logged in student has given against all questions.
                        // 
                        if (!$exam->testcase) {
                                foreach ($allQs as $qObj) {
                                        if (($answer = Answer::findFirst(array(
                                                    'conditions' => 'student_id = :student: AND question_id = :question:',
                                                    'bind'       => array(
                                                            'student'  => $student->id,
                                                            'question' => $qObj->id
                                                    ))
                                            ))) {
                                                $answers[$qObj->id] = $answer;
                                        }
                                }
                        }
                }

                // 
                // Get list of all questions that this student has asked to highlight:
                // 
                $highlightedQuestList = array();
                if (!$exam->testcase) {
                        foreach ($allQs as $q) {
                                if (($allAns = $q->getAnswers('student_id = ' . $student->id))) {
                                        foreach ($allAns as $stAns) {
                                                $stAnsData = json_decode($stAns->answer, true);
                                                if (isset($stAnsData['highlight-q']) && $stAnsData['highlight-q'] == 'yes') {
                                                        $highlightedQuestList[] = $stAns->question_id;
                                                }
                                        }
                                }
                        }
                }

                $params = array(
                        'exam'          => $exam,
                        'questions'     => $allQs,
                        'quest'         => $questions,
                        'answer'        => $answers,
                        'highlightedQs' => $highlightedQuestList,
                        'viewMode'      => $viewMode,
                        'testMode'      => $exam->testcase,
                        'student'       => $student
                );

                $this->view->setVars($params);
                $this->view->setLayout('thin-layout');
        }

        /**
         * Question correction
         * Allows corrector's to check student answers in exam.
         * 
         * exam/{exam_id}/correction/{correction-by}/{question_id}
         * 
         * Allowed to Roles: corrector, decoder
         */
        public function correctionAction($eid, $loadAnswersBy = null, $loadBy = array())
        {
                //
                // Sanitize:
                // 
                $eid = $this->dispatcher->getParam("examId", "int");

                // 
                // Load exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Find display mode:
                // 
                preg_match('/^\/([a-z]+)\/([0-9]+)/', $loadAnswersBy, $loadBy);

                // 
                // Use corrector role unless being creator or decoder:
                // 
                if (!($this->user->acquire(array(Roles::CREATOR, Roles::DECODER), $eid))) {
                        $this->user->setPrimaryRole(Roles::CORRECTOR);
                }

                // 
                // Show anonymous code during correction:
                // 
                if (($exam->details & Exam::SHOW_CODE_DURING_CORRECTION) != 0) {
                        $exam->show_code = true;
                } else {
                        $exam->show_code = false;
                }

                if (count($loadBy)) {

                        switch ($loadBy[1]) {
                                case 'student':
                                        $this->correctionLoadStudent($exam, $loadBy[2]);
                                        break;
                                case 'question':
                                        $this->correctionLoadQuestion($exam, $loadBy[2]);
                                        break;
                                case 'answer':
                                        $this->correctionLoadAnswer($exam, $loadBy[2]);
                                        break;
                                default:
                                        throw new Exception("Unable to load answers for provided criteria");
                        }

                        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                        $this->view->pick('question/answers');
                } else {
                        $this->correctionLoadBoard($exam);
                }

                // 
                // Reset primary role as the view is not correctly dealing 
                // with roles yet.
                // 
                $this->user->setPrimaryRole(null);
        }

        /**
         * Get data for student correction.
         * @param Exam $exam The exam.
         * @param int $sid The student ID.
         */
        private function correctionLoadStudent($exam, $sid)
        {
                // 
                // Find questions and answers for student:
                // 
                if (!($student = Student::findFirst($sid))) {
                        throw new Exception("Failed fetch student model", Error::BAD_REQUEST);
                }
                if (!($answers = $student->answers)) {
                        throw new Exception("Failed fetch answer models", Error::BAD_REQUEST);
                }
                if (!($questions = Question::find(array(
                            'conditions' => "exam_id = :exam: AND status = 'active'",
                            'bind'       => array(
                                    'exam' => $exam->id
                            ),
                            'order'      => 'slot ASC'
                    )))) {
                        throw new Exception("Failed fetch question models", Error::BAD_REQUEST);
                }

                if ($exam->show_code) {
                        $this->view->setVars(array(
                                'heading' => sprintf('Student (Code: %s)', $student->code),
                                'loadBy'  => 'student'
                        ));
                } else {
                        $this->view->setVars(array(
                                'heading' => sprintf('Student (ID: %d)', $sid),
                                'loadBy'  => 'student'
                        ));
                }

                // 
                // Pass data to view:
                // 
                $this->view->setVars(array(
                        'exam'      => $exam,
                        'answers'   => $answers,
                        'questions' => $questions,
                        'students'  => array($student)
                ));
        }

        /**
         * Get data for answer correction.
         * @param Exam $exam The exam.
         * @param int $aid The answer ID.
         */
        private function correctionLoadAnswer($exam, $aid)
        {
                // 
                // Get answer, question and student:
                // 
                if (!($answer = Answer::findFirst($aid))) {
                        throw new Exception("Failed fetch answer model", Error::BAD_REQUEST);
                }

                if (!($question = $answer->question)) {
                        throw new Exception("Failed fetch question model", Error::BAD_REQUEST);
                }
                if (!($student = $answer->student)) {
                        throw new Exception("Failed fetch student model", Error::BAD_REQUEST);
                }

                if ($exam->show_code) {
                        $this->view->setVars(array(
                                'heading' => sprintf('Question (Q%d) answered by student (Code: %s)', $question->slot, $student->code),
                                'loadBy'  => 'answer'
                        ));
                } else {
                        $this->view->setVars(array(
                                'heading' => sprintf('Question (Q%d) answered by student (ID: %d)', $question->slot, $answer->student_id),
                                'loadBy'  => 'answer'
                        ));
                }

                // 
                // Pass data to view:
                // 
                $this->view->setVars(array(
                        'exam'      => $exam,
                        'answers'   => array($answer),
                        'questions' => array($question),
                        'students'  => array($student)
                ));
        }

        /**
         * Get data for question correction.
         * @param Exam $exam The exam.
         * @param int $qid The question ID.
         */
        private function correctionLoadQuestion($exam, $qid)
        {
                // 
                // Find question and its answers:
                // 
                if (!($question = Question::findFirst($qid))) {
                        throw new Exception("Failed fetch question model", Error::BAD_REQUEST);
                }
                if (!($answers = $question->answers)) {
                        throw new Exception("Failed fetch answer model", Error::BAD_REQUEST);
                }

                $this->view->setVars(array(
                        'heading' => sprintf('Question (Q%d)', $question->slot),
                        'loadBy'  => 'question'
                ));

                if ($exam->show_code) {
                        // 
                        // Order answers by student code:
                        // 
                        $sorted = array();

                        foreach ($answers as $answer) {
                                $student = $answer->student;
                                $sorted[$student->code] = $answer;
                        }

                        if (!ksort($sorted, SORT_STRING)) {
                                unset($sorted);
                        } else {
                                unset($answers);
                                $answers = $sorted;
                        }
                }

                // 
                // Pass data to view:
                // 
                $this->view->setVars(array(
                        'exam'      => $exam,
                        'answers'   => $answers,
                        'questions' => array($question)
                ));
        }

        /**
         * Get data for score board.
         * @param Exam $exam The exam.
         */
        private function correctionLoadBoard($exam)
        {
                // 
                // Fetch all data for score board:
                // 
                if (!($questions = Question::find(array(
                            'conditions' => "exam_id = :exam: AND status = 'active'",
                            'bind'       => array(
                                    'exam' => $exam->id
                            ),
                            'order'      => 'slot ASC'
                    )))) {
                        throw new Exception("Failed fetch question models", Error::BAD_REQUEST);
                }
                if (!($students = $exam->students)) {
                        throw new Exception("Failed fetch student models", Error::BAD_REQUEST);
                }

                // 
                // Pass data to view:
                // 
                $this->view->setVars(array(
                        'exam'      => $exam,
                        'questions' => $questions,
                        'students'  => $students
                ));
        }

}
