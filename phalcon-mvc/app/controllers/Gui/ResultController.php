<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ResultController.php
// Created: 2014-10-15 20:53:40
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Gui;

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Exam\Result\Compress;
use OpenExam\Library\Core\Exam\Result\Download;
use OpenExam\Library\Core\Exam\Result\Queued as ResultHandler;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Models\Exam;
use OpenExam\Models\Render;
use OpenExam\Models\Student;
use Phalcon\Mvc\View;

/**
 * Controller for performing result related operations.
 * 
 * This controller was historical used for downloading results as student. All
 * rendering is now handled by the render queue, and download of results as 
 * student has moved to the utility\render controller that is also enforcing
 * access control durin exams.
 * 
 * The only student related action left is view used to generate the HTML 
 * for rendering results as student.
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ResultController extends GuiController
{

        public function initialize()
        {
                parent::initialize();

                // 
                // Configure URL service to return full domain if called back from a render task.
                // 
                if ($this->request->has('token') &&
                    $this->request->has('render') &&
                    $this->request->get('render') == 'result') {
                        $this->url->setBaseUri(
                            sprintf("%s://%s/%s/", $this->request->getScheme(), $this->request->getServerName(), trim($this->config->application->baseUri, '/'))
                        );
                }
        }

        /**
         * Result viewing for an exam.
         * 
         * The calling student can download his own exam result only. If caller
         * is staff, the check if person is among those having permission to 
         * download result of all students.
         */
        public function viewAction()
        {
                // 
                // Get sanitized request parameters:
                // 
                if (!($eid = $this->dispatcher->getParam("exam_id"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($sid = $this->dispatcher->getParam("student_id"))) {
                        throw new Exception("Missing or invalid question ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find exam", Error::PRECONDITION_FAILED);
                }

                // 
                // Get student model:
                // 
                if (!($student = Student::findFirst($sid))) {
                        throw new ModelException("Failed find student", Error::PRECONDITION_FAILED);
                }

                // 
                // Authorize access.
                // 
                $handler = new ResultHandler($exam);
                if (!$handler->canAccess($sid)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                $data = array();
                $sids = array();

                $sids[] = $student->id;
                $data['students'][] = $student;

                // 
                // Fetch question data:
                // 
                $data['examScore'] = 0;
                $questions = $exam->getQuestions(array("order" => "slot", 'conditions' => "status = 'active'"));

                foreach ($questions as $question) {

                        $qScore = 0;
                        $qParts = json_decode($question->quest, true);
                        foreach ($qParts as $qPart) {
                                $qScore += $qPart['q_points'];
                        }
                        $data['questions'][] = $question;

                        // 
                        // Question parts data to be passed to view:
                        // 
                        $data['qData'][$question->id]['qParts'] = $qParts;

                        // 
                        // Question correctors data:
                        // 
                        foreach ($question->getCorrectors() as $corrector) {
                                $data['qCorrectors'][$corrector->user][] = $question->name;
                        }

                        // 
                        // Fetch answers and result data against this question:
                        // 
                        $answers = $question->getAnswers(
                            "question_id = " . $question->id . " AND student_id = " . $student->id
                        );

                        foreach ($answers as $answer) {
                                if (($result = $answer->getResult())) {

                                        $qPartsResult = json_decode($result->score, true);

                                        // 
                                        // Store data if required for this student:
                                        // 
                                        if (in_array($answer->student_id, $sids)) {
                                                $data['answers'][$student->id][$question->id] = json_decode($answer->answer, true);
                                                $data['results'][$student->id][$question->id] = $qPartsResult;
                                                $data['results'][$student->id][$question->id]["comments"] = $result->comment;
                                                $data['results'][$student->id][$question->id]["correction"] = $result->correction;
                                        }

                                        foreach ($qPartsResult as $part => $score) {
                                                $data['studentScore'][$answer->student_id] += $score;
                                        }

                                        unset($qPartsResult);
                                        unset($answer);
                                }
                        }

                        // 
                        // Exam score:
                        // 
                        $data['examScore'] += $qScore;

                        unset($answers);
                        unset($question);
                        unset($qParts);
                }

                // 
                // Calculate exam grades:
                // 
                $grades = preg_split('/[\r\n]+/', $exam->grades);
                foreach ($grades as $grade) {
                        $t = explode(":", $grade);
                        $data['examGrades'][$t[0]] = $t[1];
                }
                arsort($data['examGrades']);

                // 
                // Calcualte student's grades:
                // 
                if (isset($data['studentScore'])) {
                        foreach ($data['studentScore'] as $id => $score) {
                                foreach ($data['examGrades'] as $grade => $limit) {
                                        if ((($score / $data['examScore']) * 100) >= $limit) {
                                                $data['studentGrade'][$id] = $grade;
                                                if (isset($data['studentGrade'][$grade])) {
                                                        $data['studentGrade'][$grade] ++;
                                                } else {
                                                        $data['studentGrade'][$grade] = 1;
                                                }
                                                break;
                                        }
                                }
                        }
                }

                $this->view->setVars(array(
                        'exam' => $exam,
                        'data' => $data
                    )
                );
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        }

        /**
         * Downloads scoreboard as faked (HTML) Excel Spreadsheet. 
         * 
         * @param int $eid The exam ID.
         * @param bool $download True for sending data.
         */
        public function exportAction($eid, $download = false)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find exam model", Error::PRECONDITION_FAILED);
                }

                // 
                // The file path:
                // 
                $source = sprintf("%s/result/%d.xls", $this->config->application->cacheDir, $exam->id);
                $target = sprintf("\"%s.xls\"", $exam->name);

                if (!$download) {
                        // 
                        // Store POST data:
                        // 
                        if (!$this->request->hasPost('score_board')) {
                                return;
                        }
                        if (!($data = $this->request->getPost('score_board'))) {
                                throw new Exception("Failed to generate Excel (HTML) spreadsheet.");
                        }

                        file_put_contents($source, utf8_decode($data));
                        print "exported";
                } else {

                        // 
                        // Use MIME hint when sending file:
                        // 
                        $this->view->disable();

                        $this->response->setFileToSend($source, $target);
                        $this->response->setContentType('application/vnd.ms-excel', 'UTF-8');

                        $this->response->send();
                }
        }

        /**
         * Archive result render jobs.
         * 
         * Pass an list of render jobs (ID) to be archived and downloaded as 
         * a ZIP-file. The list should be named render.
         * 
         * @param int $eid The exam ID.
         * @throws Exception
         * @throws ModelException
         */
        public function archiveAction($eid)
        {
                $this->view->disable();

                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($rid = $this->request->get("render"))) {
                        throw new Exception("Missing or invalid render ID's", Error::PRECONDITION_FAILED);
                }
                if (!is_array($rid)) {
                        throw new Exception("Expected array of render ID's", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find exam model", Error::PRECONDITION_FAILED);
                }

                // 
                // Get all render models:
                // 
                if (!($renders = Render::find(array(
                            'conditions' => sprintf("id IN (%s)", implode(",", $rid)
                        ))))) {
                        throw new Exception("Failed lookup render models");
                }

                // 
                // The download parameters:
                // 
                $name = sprintf("%s - %s - %d", $exam->name, $exam->starttime, array_sum($rid));
                $path = sprintf("result/%d-%d.zip", $exam->id, md5($name));

                // 
                // Create custom archive:
                // 
                $compress = new Compress();
                $compress->setName($name);
                $compress->setPath($path);

                // 
                // Add all render jobs to archive:
                // 
                foreach ($renders as $render) {
                        $compress->addFile($render->path, $render->user);
                }

                // 
                // Create the archive file:
                // 
                if (!$compress->exist()) {
                        $compress->create();
                }

                // 
                // Return download URL:
                // 
                $this->response->setContent("exam=$eid&name=$name&path=$path");
                $this->response->send();
        }

        /**
         * Action for downloading exam archive.
         * 
         * Call archive action to create and exam archive. The parameters returned 
         * from that action can be used for downloading the archive from this 
         * action.
         * 
         * @param int $eid The exam ID.
         * @param string $name The archive name.
         * @throws Exception
         */
        public function downloadAction()
        {
                $this->view->disable();

                //
                // Sanitize:
                // 
                if (!($eid = $this->request->get('exam', "string"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($name = $this->request->get('name', "string"))) {
                        throw new Exception("Missing or invalid name parameter", Error::PRECONDITION_FAILED);
                }
                if (!($path = $this->request->get('path', "string"))) {
                        throw new Exception("Missing or invalid path parameter", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Re-construct compress archive:
                // 
                $compress = new Compress();
                $compress->setName($name);
                $compress->setPath($path);

                // 
                // Use downloader for sending archive (might block):
                // 
                $download = new Download();
                $download->sendArchive($compress);
        }

}
