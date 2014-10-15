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

use  OpenExam\Controllers\GuiController;
use  OpenExam\Models\Exam;
use  OpenExam\Models\Student;

/**
 * Controller for loading Exam pages
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class ExamController extends GuiController
{
        /**
         * @ToDO: consider alternatives
         * constants
         */
        const STAFF_ROLE   = 'staff';
        const STUDENT_ROLE = 'student';
        
	/**
         * Home page for exam management to list all exams
	 * exam/index
	 */
        public function indexAction()
        {
                // initializations
                $mainRole = self::STAFF_ROLE;
                $loggedIn = $this->session->get('authenticated');
                
                // check if this person exists in students table
                $isStudent = Student::findFirst("user = '".$loggedIn['user']."'");
                if($isStudent) {
                        $mainRole = self::STUDENT_ROLE;
                        
                        $exams['upcoming-exams'] = $this->phql
                                ->executeQuery(
                                        "select e.id, e.name, e.descr, e.starttime, e.endtime from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Student s "
                                        .   "where s.user = :user: and endtime >= NOW() order by endtime desc",
                                        array("user" => $loggedIn['user'])
                        );

                        $exams['finished-exams'] = $this->phql
                                ->executeQuery(
                                        "select e.id, e.name, e.descr, e.starttime, e.endtime from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Student s "
                                        .   "where s.user = :user: and endtime < NOW() order by endtime desc",
                                        array("user" => $loggedIn['user'])
                        );
                        
                } else {
                        $mainRole = self::STAFF_ROLE;
                        
                        $colList = "e.id, e.name, e.descr, e.starttime, e.endtime";
                        $exams['manager'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "where creator = :user: order by id desc",
                                        array("user" => $loggedIn['user'])
                        );

                        $exams['contributor'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Contributor c "
                                        .   "where c.user = :user: ",
                                        array("user" => $loggedIn['user'])
                        );

                        $exams['decoder'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Decoder d "
                                        .   "where d.user = :user: ",
                                        array("user" => $loggedIn['user'])
                        );

                        $exams['invigilator'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Invigilator i "
                                        .   "where i.user = :user: ",
                                        array("user" => $loggedIn['user'])
                        );

                        $exams['corrector'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Question q "
                                        .   "inner join OpenExam\Models\Corrector c "
                                        .   "where c.user = :user:",
                                        array("user" => $loggedIn['user'])
                        );

                }
                
                // pass data to view
                $this->view->setVars(
                    array(
                        'mainRole' => $mainRole,
                        'roleBasedExamList' => $exams
                    )
                );
        }

	/**
         * Show create view for exam
	 * On exam create request, new records are inserted 
         * exam/create
         * 
	 */
        public function createAction()
        {
                
                $loggedIn = $this->session->get('authenticated');
                
                // Insert a new record in exam table whenever someone tries to create a new exam
                if (!$this->session->has('draft-exam-id')) {
                        
                        // create a new exam
                        $exam = new \OpenExam\Models\Exam();
                        $examSaved = $exam->save( array(
                                'name'    => ' ',
                                'descr'   => ' ',
                                'creator' => $loggedIn['user'],
                                'orgunit' => 'MedfarmDoIT',
                                'grades'  => ' '
                        ));
                        
                        if ($examSaved) {
							
				// add a default topic for this exam
				$topic = new \OpenExam\Models\Topic();
				$topicSaved = $topic->save( array(
						'exam_id'	=> $exam->id,
						'name'   	=> 'Default section',
						'randomize' => '0'
				));
							
                                // save exam id in session for further loads
                                $this->session->set('draft-exam-id', $exam->id);
                                
                        } else {
                                
                                $errorMsg = 'Failed to initialize exam';
                                throw new \Exception($errorMsg);
                        }
                } else {
                        // load and pass the data of draft exam 
                        $exam = \OpenExam\Models\Exam::findFirst($this->session->get('draft-exam-id'));
                }

                $this->view->setVar("exam", $exam);
        }
        
	/**
         * Update view for exam
	 * exam/update/{exam-id}
	 */
        public function updateAction($examId)
        {
                // sanitize
                $examId = $this->filter->sanitize($examId, "int");
                
                // fetch data
                $exam = Exam::findFirst($examId);
                
                // pass data to view
		$this->view->setVar('exam', $exam);
         }
        
        /**
         * Shows exam instructions for student and for test exam
	 * exam/{exam_id}
	 */
        public function instructionAction($examId)
        {
                // sanitize
                $examId = $this->filter->sanitize($this->dispatcher->getParam("examId"), "int");
                
                // fetch exam data if it has not been finished yet
                $exam = Exam::findFirst("id = " . $examId . " and endtime > NOW()");
                if(!$exam) {
                        return $this->response->redirect('exam/index');
                }
                
                $this->view->setVar("exam", $exam);
                $this->view->setLayout('thin-layout');
        }

        /**
         * Load popup for student management under the exam
	 * exam/students
	 */
        public function studentsAction()
        {
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        }
        
}
