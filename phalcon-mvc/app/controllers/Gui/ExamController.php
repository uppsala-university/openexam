<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExamController.php
// Created: 2014-09-18 18:11:50
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Gui\Component\DateTime;
use OpenExam\Library\Gui\Component\Phase;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Corrector;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Topic;
use Phalcon\Mvc\View;

/**
 * Controller for loading Exam pages
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class ExamController extends GuiController
{

        /**
         * Exams to list per page on exam/index
         */
        const EXAMS_PER_PAGE = 3;

        /**
         * Home page for exam management to list all exams
         * exam/index
         */
        public function indexAction()
        {
                // 
                // Get all published exams using the student role:
                // 
                $this->user->setPrimaryRole(Roles::STUDENT);
                if (!($exams['student'] = Exam::find(array(
                            'conditions' => "published = 'Y'",
                            'order'      => 'starttime DESC'
                    )))) {
                        throw new \Exception("Failed query student exams");
                }
                // 
                // Filter student exams on upcoming and ongoing:
                // 
                $exams['student-upcoming'] = $exams['student']->filter(function($exam) {
                        if ($exam->state & State::UPCOMING || $exam->state & State::RUNNING) {
                                return $exam;
                        }
                });
                // 
                // Filter student exams on finished:
                // 
                $exams['student-finished'] = $exams['student']->filter(function($exam) {
                        if ($exam->state & State::FINISHED) {
                                return $exam;
                        }
                });
                unset($exams['student']);

                // 
                // Get exams using the creator role:
                // 
                $this->user->setPrimaryRole(Roles::CREATOR);
                if (!($exams['creator'] = Exam::find(array(
                            'order' => 'created DESC'
                    )))) {
                        throw new \Exception("Failed query creator exams");
                }

                // 
                // Get exams using the contributor role:
                // 
                $this->user->setPrimaryRole(Roles::CONTRIBUTOR);
                if (!($exams['contributor'] = Exam::find(array(
                            'order' => 'created DESC'
                    )))) {
                        throw new \Exception("Failed query contributor exams");
                }

                // 
                // Get exams using the invigilator role:
                // 
                $this->user->setPrimaryRole(Roles::INVIGILATOR);
                if (!($exams['invigilator'] = Exam::find(array(
                            'order' => 'created DESC'
                    )))) {
                        throw new \Exception("Failed query invigilator exams");
                }

                // 
                // Get exams using the corrector role:
                // 
                $this->user->setPrimaryRole(Roles::CORRECTOR);
                if (!($exams['corrector'] = Exam::find(array(
                            'order' => 'created DESC'
                    )))) {
                        throw new \Exception("Failed query corrector exams");
                }

                // 
                // Get exams using the decoder role:
                // 
                $this->user->setPrimaryRole(Roles::DECODER);
                if (!($exams['decoder'] = Exam::find(array(
                            'order' => 'created DESC'
                    )))) {
                        throw new \Exception("Failed query decoder exams");
                }

                // 
                // Reset primary role:
                // 
                $this->user->setPrimaryRole(null);

                // 
                // Set data for view:
                // 
                $this->view->setVar('roleBasedExamList', $exams);

                // 
                // Cleanup:
                // 
                unset($exams);
        }

        /**
         * Show create view for exam
         * On exam create request, new records are inserted 
         * exam/create
         * 
         * Allowed to Roles: teacher
         */
        public function createAction()
        {
                $exam = new Exam();

                if ($exam->save(array(
                            'name'    => ' ',
                            'descr'   => ' ',
                            'creator' => $this->user->getPrincipalName(),
                            'grades'  => 'U:0&#13;&#10;G:50&#13;&#10;VG:75',
                            'details' => 7
                    )) == false) {
                        throw new \Exception(
                        sprintf("Failed to initialize exam (%s)", $exam->getMessages()[0])
                        );
                }

                return $this->response->redirect('exam/update/' . $exam->id . '/creator/new-exam');
        }

        /**
         * Update view for exam
         * exam/update/{exam-id}
         * 
         * Allowed to Roles: creator, contributor
         */
        public function updateAction($examId)
        {
                // 
                // Sanitize:
                // 
                if (!($examId = $this->filter->sanitize($examId, "int"))) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check if role has been passed:
                // 
                $params = $this->dispatcher->getParams();
                if (isset($params[1]) && in_array($params[1], $this->capabilities->getRoles())) {
                        $this->user->setPrimaryRole($params[1]);
                } else {
                        throw new \Exception("Invalid URL.");
                }

                // 
                // Fetch data:
                // 
                $exam = Exam::findFirst($examId);

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam' => $exam
                ));

                // 
                // Cleanup:
                // 
                unset($exam);
                unset($params);
        }

        /**
         * Allows exam creator to replicate his exam
         * 
         * exam/replicate/{exam-id}
         * Allowed to Roles: teacher
         */
        public function replicateAction($examId)
        {
                if (!($examId = $this->filter->sanitize($examId, "int"))) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                if (!$this->user->roles->aquire(Roles::CREATOR, $examId) ||
                    !$this->user->roles->aquire(Roles::ADMIN, $examId)) {
                        throw new \Exception("Only creator or admins can replicate exams", Error::FORBIDDEN);
                }

                if (($oldExam = Exam::findFirst($examId)) == null) {
                        throw new \Exception("Failed lookup exam", Error::INTERNAL_SERVER_ERROR);
                }

                // 
                // Create new exam with inherited properties:
                // 
                $newExam = new Exam();

                if ($newExam->save(array(
                            "name"    => $oldExam->name,
                            "descr"   => $oldExam->descr,
                            "creator" => $oldExam->creator,
                            "details" => $oldExam->details,
                            "orgunit" => $oldExam->orgunit,
                            "grades"  => $oldExam->grades
                    )) == false) {
                        throw new \Exception(sprintf("Failed save new exam (%s)", $newExam->getMessages()[0]));
                }

                // 
                // Replicate other data if options are provided for replication.
                // 

                if ($this->request->hasPost('options')) {
                        $replicateOpts = $this->request->getPost('options');

                        // 
                        // Map between old and new topics:
                        // 
                        $topicsMap = array();

                        // 
                        // Replicate topics if selected:
                        // 
                        if (in_array('topics', $replicateOpts)) {
                                // 
                                // Replicate topics. Keep track on new topics by
                                // adding them to the topics map.
                                // 
                                foreach ($oldExam->topics as $oldTopic) {
                                        if ($oldTopic->name == 'default') {
                                                $topicsMap[$oldTopic->id] = $newExam->topics[0]->id;
                                                continue;
                                        }

                                        $newTopic = new Topic();
                                        if ($newTopic->save(array(
                                                    "exam_id"   => $newExam->id,
                                                    "name"      => $oldTopic->name,
                                                    "randomize" => $oldTopic->randomize,
                                                    "grades"    => $oldTopic->grades,
                                                    "depend"    => $oldTopic->depend
                                            )) == false) {
                                                throw new \Exception(sprintf("Failed duplicate topic (%s)", $newTopic->getMessages()[0]));
                                        }
                                        $topicsMap[$oldTopic->id] = $newTopic->id;

                                        unset($oldTopic);
                                        unset($newTopic);
                                }
                        } else {
                                // 
                                // Map all topics to default topic:
                                // 
                                foreach ($oldExam->topics as $oldTopic) {
                                        $topicsMap[$oldTopic->id] = $newExam->topics[0]->id;
                                        unset($oldTopic);
                                }
                        }

                        // 
                        // Replicate questions and correctors if selected:
                        // 
                        if (in_array('questions', $replicateOpts)) {
                                foreach ($oldExam->questions as $oldQuest) {

                                        // 
                                        // Replicate question:
                                        // 
                                        $newQuest = new Question();

                                        if ($newQuest->save(array(
                                                    "exam_id"  => $newExam->id,
                                                    "topic_id" => $topicsMap[$oldQuest->topic_id],
                                                    "score"    => $oldQuest->score,
                                                    "name"     => $oldQuest->name,
                                                    "quest"    => $oldQuest->quest,
                                                    "status"   => $oldQuest->status,
                                                    "comment"  => $oldQuest->comment,
                                                    "grades"   => $oldQuest->grades
                                            )) == false) {
                                                throw new \Exception(sprintf("Failed duplicate question (%s)", $newQuest->getMessages()[0]));
                                        }

                                        // 
                                        // Replicate question correctors:
                                        // 
                                        foreach ($oldQuest->correctors as $oldCorrector) {
                                                $newCorrector = new Corrector();
                                                if ($newCorrector->save(array(
                                                            "question_id" => $newQuest->id,
                                                            "user"        => $oldCorrector->user
                                                    )) == false) {
                                                        throw new \Exception(sprintf("Failed duplicate corrector (%s)", $newCorrector->getMessages()[0]));
                                                }

                                                unset($newCorrector);
                                                unset($oldCorrector);
                                        }

                                        unset($newQuest);
                                        unset($oldQuest);
                                }
                        }

                        // 
                        // Replicate roles if selected.
                        // 
                        if (in_array('roles', $replicateOpts)) {
                                // 
                                // The roles to be replicated:
                                // 
                                $roles = array(
                                        'contributors' => '\OpenExam\Models\Contributor',
                                        'decoders'     => '\OpenExam\Models\Decoder',
                                        'invigilators' => '\OpenExam\Models\Invigilator',
                                );

                                foreach ($roles as $role => $class) {
                                        foreach ($oldExam->$role as $member) {

                                                // 
                                                // Skip user added by behavior.
                                                // 
                                                if ($member->user == $this->user->getPrincipalName()) {
                                                        continue;
                                                }

                                                $newRole = new $class();
                                                if ($newRole->save(array(
                                                            "exam_id" => $newExam->id,
                                                            "user"    => $member->user
                                                    )) == false) {
                                                        throw new \Exception("Failed duplicate role");
                                                }

                                                unset($newRole);
                                        }
                                }
                        }

                        unset($replicateOpts);
                        unset($topicsMap);
                }

                $this->session->set('draft-exam-id', $newExam->id);

                $this->view->disable();
                $this->response->setJsonContent(array(
                        "status"  => "success",
                        "exam_id" => $newExam->id
                ));
                $this->response->send();

                // 
                // Cleanup:
                // 
                unset($oldExam);
                unset($newExam);
        }

        /**
         * Shows exam instructions for student and for test exam
         * exam/{exam_id}
         * 
         * Allowed to Roles: student
         */
        public function instructionAction($examId)
        {
                // 
                // Sanitize:
                // 
                if (!($examId = $this->filter->sanitize(
                    $this->dispatcher->getParam("examId"), "int")
                    )) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Fetch exam data either as creator or student:
                // 
                if ($this->user->roles->aquire(Roles::CREATOR, $examId)) {
                        $this->user->setPrimaryRole(Roles::CREATOR);
                } else {
                        $this->user->setPrimaryRole(Roles::STUDENT);
                }

                // 
                // Redirect to start page if exam is missing:
                // 
                if (!($exam = Exam::findFirst($examId))) {
                        return $this->response->redirect($this->config->session->startPage);
                }

                // 
                // Check that exam has not finished:
                // 
                if ($this->user->getPrimaryRole() == Roles::STUDENT) {
                        if ($exam->state & State::FINISHED) {
                                return $this->response->redirect($this->config->session->startPage);
                        }
                }

                // 
                // Set test mode if exam creator:
                // 
                if ($this->user->getPrimaryRole() == Roles::CREATOR) {
                        $this->view->setVar('testMode', true);
                } else {
                        $this->view->setVar('testMode', false);
                }

                // 
                // Set exam and layout for instructions view:
                // 
                $this->view->setVar('exam', $exam);
                $this->view->setLayout('thin-layout');

                // 
                // Cleanup:
                // 
                unset($exam);
        }

        /**
         * Load popup for student management under the exam
         * exam/students
         * 
         * Allowed to Roles: invigilator
         */
        public function studentsAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (!($examId = $this->request->get('exam_id', "int"))) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (!($exam = Exam::findFirst(array(
                            'conditions' => 'id = :exam: AND (endtime IS NULL OR endtime > NOW())',
                            'bind'       => array(
                                    'exam' => $examId
                            )
                    )))) {
                        throw new \Exception("Sorry! "
                        . "Exam time has been finished. <br>"
                        . "It is no more possible to manage student's data.");
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        "exam"    => $exam,
                        "domains" => $this->catalog->getDomains()
                ));

                // 
                // Cleanup:
                // 
                unset($exam);
        }

        /**
         * Load popup for exam settings
         * exam/settings
         * 
         * Allowed to Roles: creator
         */
        public function settingsAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (!($examId = $this->request->get('exam_id', "int"))) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (!($exam = Exam::findFirst($examId))) {
                        throw new \Exception("Failed find requested exam.", Error::BAD_REQUEST);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVar("exam", $exam);

                // 
                // Cleanup:
                // 
                unset($exam);
        }

        /**
         * Load popup for exam security
         * exam/security
         * 
         * Allowed to Roles: creator
         */
        public function securityAction()
        {
                // 
                // Render view as popup page:
                // 
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize:
                // 
                if (($examId = $this->request->getPost("exam_id", "int")) == null) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (($exam = Exam::findFirst($examId)) == false) {
                        throw new \Exception("Failed find requested exam.", Error::BAD_REQUEST);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        "active" => $this->location->getActive($examId),
                        "system" => $this->location->getSystem(),
                        "recent" => $this->location->getRecent(),
                        "exam"   => $exam
                ));

                // 
                // Cleanup:
                // 
                unset($exam);
        }

        /**
         * Action for pending exam access.
         */
        public function pendingAction()
        {
                // 
                // Get forwarded exam:
                // 
                $exam = $this->dispatcher->getParam('exam');

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam'  => $exam,
                        'icon'  => $this->url->get('/img/clock.png'),
                        'retry' => $this->url->get('/exam/' . $exam->id . '/question/1')
                ));

                // 
                // Cleanup:
                // 
                unset($exam);
        }

        /**
         * View for displaying exam details (i.e. state).
         * @param int $examId The exam ID.
         * @throws \Exception
         */
        public function detailsAction($examId)
        {
                // 
                // Sanitize:
                // 
                if (!($examId = $this->filter->sanitize($examId, "int"))) {
                        throw new \Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Try to find exam in request parameter:
                // 
                if (($exam = Exam::findFirst($examId)) == false) {
                        throw new \Exception("Failed find requested exam.", Error::BAD_REQUEST);
                }

                // 
                // Set view data:
                // 
                $this->view->setVars(array(
                        'exam'     => $exam,
                        'phase'    => new Phase($exam->getState()),
                        'datetime' => new DateTime($exam->starttime, $exam->endtime)
                ));

                // 
                // Cleanup:
                // 
                unset($exam);
        }

}
