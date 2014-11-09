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
         * result/view/{exam_id}
         */
        public function viewAction()
        {
                $data = array();
                $studentIds = array();
                
                // sanitize
                $examId = $this->filter->sanitize($this->dispatcher->getParam('examId'), 'int');
                
                
                ## get exam data 
                $exam   = Exam::findFirst($examId);
                
                
                ## get exam student's data (if studnet requested himself)
                //$student = $exam->getStudents("user = '".$this->user->getPrincipalName()."'");
                $student= Student::findFirst(array(
                                "conditions" => "exam_id = ?1 and user = ?2",
                                "bind"       => array(
                                                1 => $examId,
                                                2 => $this->user->getPrincipalName()
                                        )
                            ));
                //@ToDO: As this person was not a student in this exam,
                //Find if this logged in person is allowed to view result
                if (!$student) {
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
                $questions = $exam->getQuestions(array("order" => "name"));
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
                                $data['qCorrectors'][$corrector][] = $question->name;
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

                                if($score >= $limit) {
                                        
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
         * Download exam result
         * 
         * result/download/{exam_id}
         */
        public function downloadAction($examId)
        {
                // sanitize 
                $examId = $this->filter->sanitize($this->dispatcher->getParam('examId'), 'int');
                
                ################# Who can download the results???????? ##########
                # If studente Id has been provided then it mea
                //$studentId = $this->filter->sanitize($this->dispatcher->getParam('studentId'), 'int');
                
                // download pdf
                $pages = array(
                      array(
                            'page'              => $this->url->get("result/".$examId."/view/".$studentId)//"https://www.bmcmediatek.uu.se/openexam-svn/result/view/1",
                            //'footer.right'      => date("Y-m-d H:i"),
                            //'pagesCount'        => TRUE,
                            //'pageOffset'        => 1,
                            //'header.htmlUrl'    => 'http://google.com'
                            //'toc.captionText'   => 'This is Toc!',
                            //'includeInOutline'  => TRUE
                              
                      )
                );
                $render = $this->render->getRender(Renderer::FORMAT_PDF);
                $render->send('file.pdf', $pages);
        }

        /**
         * Decode result of the exam
         * 
         * result/decode/{exam_id}
         */
        public function decodeAction()
        {
        }

        /**
         * Generate exam summary to show to teachers
         * 
         * result/summary/{exam_id}
         */
        public function summaryAction()
        {
        }
        
}
