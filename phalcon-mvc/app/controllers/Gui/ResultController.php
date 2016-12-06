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

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Core\Result as ResultHandler;
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

        /**
         * Generate and save student result in PDF format.
         * 
         * @param int $examId
         * @param int $studentId
         * 
         * result/{exam_id}/generate/{student_id}
         */
        public function generateAction($examId, $studentId)
        {
                $result = new ResultHandler($examId);

                // 
                // Authorize access.
                // 
                if (!$result->canAccess($studentId)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                // 
                // Create the result file.
                // 
                $result->createFile($studentId);
                return $this->response->setJsonContent(array("stId" => $studentId));
        }

        /**
         * Download individual PDF-file or zip-archive containing all.
         * 
         * result/{exam_id}/download
         * Allowed to Roles: contributor, 
         */
        public function downloadAction($examId, $studentId = null)
        {
                $result = new ResultHandler($examId);

                // 
                // Authorize access.
                // 
                if (!$result->canAccess($studentId)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                // 
                // Download result:
                // 
                if (isset($studentId)) {
                        $result->downloadFile($studentId);
                } else {
                        $result->downloadArchive();
                }
        }

        /**
         * Result viewing against an exam
         * Logged in person can download his own exam result only.
         * 
         * If logged in person was not a student in this exam, check if he is 
         * among those who have permission to download result of all students 
         * in this exam.
         * 
         * In this case, check if allowed person has request to download result 
         * of a specific studnet (by student_id). Can have 1 pdf against 1 student.
         * Otherwise dump results of all students in this exam in one view. 
         * (result of all students in 1 pdf)
         * 
         * result/{exam_id}/view
         * 
         * Allowed to Roles: creator
         */
        public function viewAction()
        {
                $data = array();
                $studentIds = array();

                // 
                // Sanitize request parameters:
                // 
                $examId = $this->filter->sanitize($this->dispatcher->getParam('examId'), 'int');
                $studId = $this->filter->sanitize($this->dispatcher->getParam('studentId'), 'int');

                // 
                // Check required parameters:
                // 
                if (empty($examId)) {
                        throw new \Exception("The exam ID is missing", Error::PRECONDITION_FAILED);
                }
                if (empty($studId)) {
                        throw new \Exception("The student ID is missing", Error::PRECONDITION_FAILED);
                }

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($examId))) {
                        throw new ModelException("Failed find exam", Error::PRECONDITION_FAILED);
                }

                // 
                // Get student model:
                // 
                if (!($student = Student::findFirst($studId))) {
                        throw new ModelException("Failed find student", Error::PRECONDITION_FAILED);
                }

                // 
                // Authorize access.
                // 
                $result = new ResultHandler($exam);
                if (!$result->canAccess($studId)) {
                        throw new ModelException("You are not allowed to access student result.", Error::FORBIDDEN);
                }

                $studentIds[] = $student->id;
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
                            "question_id = " . $question->id
                        );

                        foreach ($answers as $answer) {
                                if (($result = $answer->getResult())) {

                                        $qPartsResult = json_decode($result->score, true);

                                        // 
                                        // Store data if required for this student:
                                        // 
                                        if (in_array($answer->student_id, $studentIds)) {
                                                $data['answers'][$student->id][$question->id] = json_decode($answer->answer, true);
                                                $data['results'][$student->id][$question->id] = $qPartsResult;
                                                $data['results'][$student->id][$question->id]["comments"] = $result->comment;
                                                $data['results'][$student->id][$question->id]["correction"] = $result->correction;
                                        }

                                        foreach ($qPartsResult as $part => $score) {
                                                $data['studentScore'][$answer->student_id] += $score;
                                        }
                                }
                        }

                        // 
                        // Exam score:
                        // 
                        $data['examScore'] += $qScore;
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
                foreach ($data['studentScore'] as $studId => $score) {
                        foreach ($data['examGrades'] as $grade => $limit) {
                                if ((($score / $data['examScore']) * 100) >= $limit) {
                                        $data['studentGrade'][$studId] = $grade;
                                        if (isset($data['studentGrade'][$grade])) {
                                                $data['studentGrade'][$grade] ++;
                                        } else {
                                                $data['studentGrade'][$grade] = 1;
                                        }
                                        break;
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
         * Generate exam summary to show to teachers
         * 
         * result/summary/{exam_id}
         */
        public function summaryAction()
        {
                
        }

        /**
         * Downloads scoreboard as faked (HTML) Excel Spreadsheet. 
         * 
         * @param int $examId The exam ID.
         * @param bool $download True for sending data.
         */
        public function exportScoreBoardAction($examId, $download = false)
        {
                $examId = $this->filter->sanitize($examId, "int");

                // 
                // Get exam data:
                // 
                if (!($exam = Exam::findFirst($examId))) {
                        throw new ModelException("Failed find exam", Error::PRECONDITION_FAILED);
                }

                // 
                // The file path:
                // 
                $source = sprintf("%s/results/%d.xls", $this->config->application->cacheDir, $exam->id);
                $target = sprintf("\"%s.xls\"", $exam->name);

                // 
                // Store POST data:
                // 
                if (!$download) {
                        if (!$this->request->hasPost('score_board')) {
                                return;
                        }
                        if (!($data = $this->request->getPost('score_board'))) {
                                throw new \Exception("Failed to generate Excel (HTML) Spreadsheet.");
                        }

                        file_put_contents($source, utf8_decode($data));
                        print "exported";
                        return;
                }

                // 
                // Use MIME hint when sending file:
                // 
                $this->view->disable();

                $this->response->setFileToSend($source, $target);
                $this->response->setContentType('application/vnd.ms-excel', 'UTF-8');

                $this->response->send();
        }

}
