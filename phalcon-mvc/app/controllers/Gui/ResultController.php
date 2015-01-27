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
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;
use OpenExam\Models\Question;
use OpenExam\Models\Answer;
use OpenExam\Models\Result;

/**
 * Controller for performing result related operations
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class ResultController extends GuiController
{

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
                
                // sanitize
                $examId = $this->filter->sanitize($this->dispatcher->getParam('examId'), 'int');
                $studentId = $this->filter->sanitize($this->dispatcher->getParam('studentId'), 'int');

                ## get exam data 
                $exam   = Exam::findFirst($examId);
                
                
                ## get exam student's data (if studnet requested himself)
                //$student = $exam->getStudents("user = '".$this->user->getPrincipalName()."'");
                $student= Student::findFirst($studentId);
                
                //@ToDO: As this person was not a student in this exam,
                //Find if this logged in person is allowed to view result
                if ($student->exam_id != $examId || !is_object($student)) {
                        die("you are not allowed!");
                        
                        // check if student id is set so show result against that student
                        $studentId = $this->filter->sanitize($this->dispatcher->getParam('studentId'), 'int');
                        
                        // show result of all students otherwise
                } else {
                        $studentIds[] = $student->id;
                        $data['students'][] = $student;
                }
                
                
                ## Fetch question data
                $data['examScore'] = 0;
                $questions = $exam->getQuestions(array("order" => "slot"));
                foreach($questions as $question) {
                        
                        $qScore = 0;
                        $qParts = json_decode($question->quest, true);
                        foreach($qParts as $qPart) {
                                $qScore += $qPart['q_points'];
                        }
                        $data['questions'][] = $question;
                        
                        // question parts data to be passed to view
                        $data['qData'][$question->id]['qParts'] = $qParts;
                        
                        // question correctors data
                        foreach($question->getCorrectors() as $corrector) {
                                $data['qCorrectors'][$corrector->user][] = $question->name;
                                
                        }
                        
                        //$data['qData'][$question->id]['qScore'] = $qScore;
                        
                        ##Fetch answers and result data against this question
                        $answers = $question->getAnswers(
                            //"student_id = ".$student->id.
                            "question_id = ".$question->id
                        );
                        
                        foreach($answers as $answer) {
                                
                                $result = $answer->getResult();
                                if(is_object($result) && $result->count()) {
                                        
                                        $qPartsResult = json_decode($result->score, true);

                                        // store data if requeired for this student
                                        if(in_array($answer->student_id, $studentIds)) {

                                                $data['answers'][$student->id][$question->id] 
                                                    = json_decode($answer->answer, true);

                                                $data['results'][$student->id][$question->id] 
                                                    = $qPartsResult;
                                                
                                                $data['results'][$student->id][$question->id]["comments"]
                                                    = $result->comment;    
                                        }

                                        foreach($qPartsResult as $part => $score) {
                                                $data['studentScore'][$answer->student_id] += $score;
                                        }
                                        
                                }
                                
                        }
                        
                        // exam score
                        $data['examScore'] += $qScore;
                }
                
                
                // Calculate exam grades
                $grades = preg_split('/[\r\n]+/', $exam->grades);
                foreach ($grades as $grade) {
                        $t = explode(":", $grade);
                        $data['examGrades'][$t[0]] =  $t[1];
                }
                arsort($data['examGrades']);
                
                // Calcualte student's grades
                foreach($data['studentScore'] as $studId => $score) {
                        
                        foreach($data['examGrades'] as $grade => $limit) {

                                if((($score/$data['examScore'])*100) >= $limit) {
                                        
                                        $data['studentGrade'][$studId] = $grade;
                                        if(isset($data['studentGrade'][$grade])) {
                                                $data['studentGrade'][$grade]++;
                                        } else {
                                                $data['studentGrade'][$grade] = 1;
                                        }
                                        
                                        break;
                                }
                        }
                }
                
                $this->view->setVars(array(
                        'exam'  => $exam,
                        'data'  => $data
                    )
                );
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        }

        /**
         * Generate and save student result in pdf format
         * 
         * @param int $examId
         * @param int $studentId
         * 
         * result/{exam_id}/generate/{student_id}
         */
        public function generateAction($examId, $studentId)
        {
                $this->view->disable();
                
                // get exam data 
                $examId = $this->filter->sanitize($examId, 'int');
                $exam = Exam::findFirst($examId);

                // get student's data
                $student = Student::findFirst(array(
                                "conditions" => "exam_id = ?1 and id = ?2",
                                "bind"       => array(
                                                1 => $examId,
                                                2 => $this->filter->sanitize($studentId, 'int')
                                        )
                            ));
                
                // save result in pdf format
                $this->_generateResultPdf($exam, $student);
                return $this->response->setJsonContent(array("stId" => $student->id));
        }

        
        /**
         * Genrate zip file, contaning result of single or all students, in pdf format
         * 
         * result/{exam_id}/download
         * 
         * Allowed to Roles: contributor, 
         */
        public function downloadAction($examId, $studentId = NULL)
        {
                
                $this->view->disable();
                
                // get exam data 
                $examId = $this->filter->sanitize($examId, 'int');
                $exam = Exam::findFirst($examId);
                
                //@ToDO-:- 
                //This is just a temporay fix because pdf rendering service is not 
                //able to keep sessions
                // generate, save result in pdf and download that pdf if it is for a student
                /*$student = Student::findFirst(array(
                        "conditions" => "exam_id = ?1 and user = ?2",
                        "bind"       => array(
                                        1 => $examId,
                                        2 => $this->user->getPrincipalName()
                                )
                    ));*/
                $student = Student::findFirst(array(
                        "conditions" => "exam_id = ?1 and id = ?2",
                        "bind"       => array(
                                        1 => $examId,
                                        2 => $studentId
                                )
                    ));
                
                if($student) {
                        
                        // get student's data
/*                        $student = Student::findFirst(array(
                                "conditions" => "exam_id = ?1 and id = ?2",
                                "bind"       => array(
                                                1 => $examId,
                                                2 => $this->filter->sanitize($studentId, 'int')
                                        )
                            ));
 */
                        // generate pdf file and download
                        $this->_generateResultPdf($exam, $student, TRUE);
                        
                } else {
                        // restrict 
                        
                        //if request was not to download result of a specific student, 
                        //we will generate zip file with all files located under results directory 
                        //for this exam
                        $cleanExamName = str_replace(" ", "-", $this->filter->sanitize($exam->name, 'string'));
                        $resultsDir = $this->config->application->cacheDir . 'results/';
                        $zipPath = $resultsDir.$cleanExamName.'.zip';
                        $filesToZipLocation = $resultsDir . $cleanExamName . '-' . $exam->id;
                        
                        if(is_dir($filesToZipLocation)) {
                                
                                //generate zip file if it don't exist
                                if(!file_exists($zipPath)) {
                                        
                                        $zip = new \ZipArchive;
                                        if($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                                                
                                                if ($handle = opendir($filesToZipLocation)) {
                                                        while (false !== ($entry = readdir($handle))) {
                                                                if ($entry != "." && $entry != ".." && strstr($entry,'.pdf')) {
                                                                        $zip->addFile($filesToZipLocation.'/'.$entry, $entry);
                                                                }
                                                        }
                                                        closedir($handle);
                                                }

                                                if(!$zip->close()) {
                                                        throw new \Exception("Failed to zip files");
                                                }
                                                
                                        } else {
                                                throw new \Exception("Failed to create zip file");
                                        }        
                                }
                                
                                $this->helper->downloadFile($zipPath);
                                
                        } else {
                                throw new \Exception("Unable to download result");
                        }
                }
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
         * Downloads score board in excel sheet format
         * -- ajax action
         */
        public function exportScoreBoardAction($examId, $downloadFile = 0)
        {
                
                $this->view->disable();
                
                $examId = $this->filter->sanitize($examId, "int");
                
                // load exam data
                $exam = Exam::findFirst($examId);
                if(!$exam) {
                        throw new \Exception("Sorry! Couldn't find this exam.");
                }
                
                $cleanExamName = str_replace(" ", "-", $this->filter->sanitize($exam->name, 'string'));
                $generateFilePath = $this->config->application->cacheDir . 
                    'results/' . $cleanExamName . '-scoreboard.xls';

                if(!$downloadFile) {
                        
                        $scoreBoardHtml = $this->request->getPost('score_board');
                        if(!empty($scoreBoardHtml)) {

                                $handle = fopen($generateFilePath, "w");
                                fwrite($handle, $scoreBoardHtml);
                                fclose($handle);
                                print "exported";
                        } else {
                                throw new \Exception("Failed to generate excel sheet.");
                        }
                        
                } else {
                        
                        if(file_exists($generateFilePath)) {
                                $this->helper->downloadFile($generateFilePath);
                        }
                }
        }

        /**
         * Generates Pdf file for a student and save it under results directory
         * 
         * @param model object $exam 
         * @param model object $student
         * @param BOOL $download if True, will download file
         */
        private function _generateResultPdf($exam, $student, $download = FALSE)
        {
                
                // where to generate file?
                $cleanExamName = str_replace(" ", "-", $this->filter->sanitize($exam->name, 'string'));
                $generateFileUnder = $this->config->application->cacheDir . 
                                'results/' . $cleanExamName . '-' . $exam->id;
                
                
                // create directories if don't exist
                if(!is_dir($generateFileUnder)) {
                        mkdir($generateFileUnder, 0775, TRUE);
                }
                
                // return true if file already exists
                $filePath = $generateFileUnder .'/'. $student->code . '.pdf';
                if(file_exists($filePath)) {
                        
                        if($download) {
                                $this->helper->downloadFile($filePath);
                        }
                        
                        return TRUE;
                }
                
                // generate pdf file
                //@ToDo: replace static url (added for testing) in $pages array ('page' index) with proper
                $pages = array(
                      array(
                            'page'              => "https://".$this->request->getServerName().$this->url->get("result/".$exam->id."/view/".$student->id),
                            //'footer.right'      => date("Y-m-d H:i"),
                            //'pagesCount'        => TRUE,
                            //'pageOffset'        => 1,
                            //'header.htmlUrl'    => 'http://google.com'
                            //'toc.captionText'   => 'This is Toc!',
                            //'includeInOutline'  => TRUE
                              
                      )
                );
                $render = $this->render->getRender(Renderer::FORMAT_PDF);
                if($download) {
                        $render->send($filePath, $pages);
                } else {
                        $render->save($filePath, $pages);
                }
        }
        
}
