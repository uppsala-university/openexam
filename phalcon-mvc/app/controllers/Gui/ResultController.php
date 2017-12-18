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
use OpenExam\Library\Core\Exam\Result as ResultHandler;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;
use Phalcon\Mvc\View;

/**
 * Controller for performing result related operations.
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
         * Generate and save student result in PDF format.
         * 
         * @param int $eid The exam ID.
         * @param int $sid The student ID.
         * 
         * @deprecated since 2.1.2
         */
        public function generateAction($eid, $sid)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($sid = $this->filter->sanitize($sid, "int"))) {
                        throw new Exception("Missing or invalid student ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // The actual PDF-generation handler:
                // 
                $result = new ResultHandler($eid);

                // 
                // Authorize access.
                // 
                if (!$result->canAccess($sid)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                // 
                // Create the result file.
                // 
                $result->createFile($sid);

                // 
                // Send JSON content:
                // 
                $this->response->setJsonContent(array("stId" => $sid));
                $this->response->send();
        }

        /**
         * Download individual PDF-file or ZIP-archive containing all.
         * 
         * @param int $eid The exam ID.
         * @param int $sid The student ID.
         * 
         * @deprecated since 2.1.2
         */
        public function downloadAction($eid, $sid = 0)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($sid = $this->filter->sanitize($sid, "int"))) {
                        throw new Exception("Missing or invalid student ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // The actual PDF-generation handler:
                // 
                $result = new ResultHandler($eid);

                // 
                // Authorize access.
                // 
                if (!$result->canAccess($sid)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                // 
                // Download result:
                // 
                if (isset($sid)) {
                        $result->downloadFile($sid);
                } else {
                        $result->downloadArchive();
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
         * Generate exam summary to show to teachers.
         * 
         * @deprecated since 2.1.2
         */
        public function summaryAction()
        {
                // 
                // Check route access:
                // 
                $this->checkAccess();
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
         * a ZIP-file. 
         * 
         * @param int $eid The exam ID.
         * @throws Exception
         * @throws ModelException
         */
        public function archiveAction($eid)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($rid = $this->request->get("rid", "array"))) {
                        throw new Exception("Missing or invalid render ID's", Error::PRECONDITION_FAILED);
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
                // TODO: create zip-file.
                // 
        }

}
