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
         * Exams to list per page on exam/index
         */
        const EXAMS_PER_PAGE = 3;
        
	/**
         * Home page for exam management to list all exams
	 * exam/index
	 */
        public function indexAction()
        {
                // initializations
                $loggedIn = $this->user;
                // check if this person exists in students table
                $isStudent = Student::findFirst("user = '".$loggedIn."'");
                if($isStudent) {
                        $baseRole = 'student';
                        $exams['student-upcoming'] = $this->phql
                                ->executeQuery(
                                        "select e.* from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Student s "
                                        .   "where s.user = :user: and endtime >= NOW() order by endtime desc "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );

                        $exams['student-finished'] = $this->phql
                                ->executeQuery(
                                        "select e.* from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Student s "
                                        .   "where s.user = :user: and endtime < NOW() order by endtime desc "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );
                        
                } else {
                        $baseRole = 'staff';
                        
                        $colList = "e.*";
                        $exams['creator'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "where creator = :user: order by id desc "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );

                        $exams['contributor'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Contributor c "
                                        .   "where c.user = :user: and e.creator != :user: "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );

                        $exams['decoder'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Decoder d "
                                        .   "where d.user = :user: "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );

                        $exams['invigilator'] = $this->phql
                                ->executeQuery(
                                        "select $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Invigilator i "
                                        .   "where i.user = :user: "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );

                        $exams['corrector'] = $this->phql
                                ->executeQuery(
                                        "select distinct $colList from OpenExam\Models\Exam e "
                                        .   "inner join OpenExam\Models\Question q "
                                        .   "inner join OpenExam\Models\Corrector c on q.id = c.question_id "
                                        .   "where c.user = :user: "
                                        //.   "limit " . self::EXAMS_PER_PAGE
                                        , array("user" => $loggedIn)
                        );
                        
                }
                
                // pass data to view
                $this->view->setVars(array(
                        'roleBasedExamList' => $exams, 
                        'baseRole' => $baseRole
                    ));
        }

	/**
         * Show create view for exam
	 * On exam create request, new records are inserted 
         * exam/create
         * 
	 */
        public function createAction()
        {
                
                $loggedIn = $this->user->getUser();
                
                // Insert a new record in exam table whenever someone tries to create a new exam
                if (!$this->session->has('draft-exam-id')) {
                        
                        // create a new exam
                        $exam = new \OpenExam\Models\Exam();
                        $examSaved = $exam->save( array(
                                'name'    => ' ',
                                'descr'   => ' ',
                                'creator' => $loggedIn,
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
                //@ToDO: get roles from access.def
                //someone will be able to access this action when he is permitted 
                //to be here
                // allowed roles for a user to be in this action
                $allowedRoleList = array('admin', 'creator', 'contributor');
                
                // get the role because of which this looged in person has got 
                // permissions to be in current controller/action
                $role = $this->user->aquire($allowedRoleList, $examId, false)[0];

                // sanitize
                $examId = $this->filter->sanitize($examId, "int");
                
                // fetch data
                $exam = Exam::findFirst($examId);
                
                // pass data to view
		$this->view->setVars(array('exam'=>$exam, 'myRole' => $role));
         }

         
	/**
         * Allows exam creator to replicate his exam
         * 
         * exam/replicate/{exam-id}
	 */
        public function replicateAction($examId)
        {
                $this->view->disable();
                
                $loggedIn = $this->user->getUser();
                $examId = $this->filter->sanitize($examId, "int");
                
                if($examId && $exam = Exam::findFirst($examId)) {
                        
                        // only exam creator can replicate an exam
                        if($exam->creator != $loggedIn)
                                 return $this->response->setJsonContent(array("status" => "failed"));

                        // create exam by replicating exam data
                        $newExam = new \OpenExam\Models\Exam();
                        $examSaved = $newExam->save(array(
                                "name" => $exam->name,
                                "descr" => $exam->descr,
                                "starttime" => $exam->starttime,
                                "endtime" => $exam->endtime,
                                "creator" => $exam->creator,
                                "details" => $exam->details,
                                "orgunit" => $exam->orgunit,
                                "grades" => $exam->grades                            
                        ));
                        if(!$examSaved)
                                return $this->response->setJsonContent(array("status" => "failed"));
                        
                        // replicate other data if options are provided for replication
                        $replicateOpts = $this->request->getPost('options');
                        if(count($replicateOpts)) {
                                ## replicate topics if selected
                                $topicMap = array();
                                if(in_array('topics', $replicateOpts)) {
                                        $topics = \OpenExam\Models\Topic::find('exam_id = ' . $exam->id);
                                        if(is_object($topics) && $topics->count()) {
                                                foreach($topics as $topic) {
                                                        $newTopic = new \OpenExam\Models\Topic();
                                                        $newTopic->save(array(
                                                              "exam_id" => $newExam->id,
                                                              "name" => $topic->name,
                                                              "randomize" => $topic->randomize,
                                                              "grades" => $topic->grades,
                                                              "depend" => $topic->depend
                                                        ));
                                                        $topicMap[$newTopic->id] = $topic->id;
                                              }
                                        }
                                }
                                
                                ## replicate questions and correctors if selected
                                if(in_array('questions', $replicateOpts)) {
                                        
                                        $questions = \OpenExam\Models\Question::find('exam_id = ' . $exam->id);
                                        if(is_object($questions) && $questions->count()) {
                                                
                                                foreach($questions as $quest) {
                                                        
                                                        // replicate questions
                                                        $newQuest = new \OpenExam\Models\Question();
                                                        $newQuest->save(array(
                                                                "exam_id" => $newExam->id,
                                                                "topic_id" => array_search($quest->topic_id, $topicMap),
                                                                "score" => $quest->score,
                                                                "name" => $quest->name,
                                                                "quest" => $quest->quest,
                                                                "status" => $quest->status,
                                                                "comment" => $quest->comment,
                                                                "grades" => $quest->grades
                                                        ));
                                                        
                                                        //replicate question correctors
                                                        $correctors = $quest->getCorrectors();
                                                        if(is_object($correctors) && $correctors->count()) {
                                                                foreach($correctors as $corrector) {
                                                                        $newCorrector = new \OpenExam\Models\Corrector();
                                                                        $newCorrector->save(array(
                                                                                "question_id" => $newQuest->id,
                                                                                "user" => $corrector->user
                                                                        ));
                                                                }        
                                                        }
                                              }
                                        }
                                        
                                        
                                }
                                
                                ## replicate roles if selected
                                if(in_array('roles', $replicateOpts)) {
                                        
                                        // roles to be replicated
                                        $roles = array(
                                                'contributors'  => '\OpenExam\Models\Contributor',
                                                'decoders'      => '\OpenExam\Models\Decoder',
                                                'invigilators'  => '\OpenExam\Models\Invigilator',
                                        );
                                        
                                        foreach($roles as $role => $roleClass) {
                                                
                                                // replicate contributors
                                                $roleUsers = $exam->$role;
                                                if(is_object($roleUsers) && $roleUsers->count()) {
                                                        foreach($roleUsers as $roleUser) {
                                                                $newRoleUser = new $roleClass();
                                                                $newRoleUser->save(array(
                                                                        "exam_id" => $newExam->id,
                                                                        "user" => $roleUser->user
                                                                ));
                                                        }        
                                                }
                                                
                                                
                                        }
                                }
                                
                        }
                        
                        $this->session->set('draft-exam-id', $newExam->id);
                        return $this->response->setJsonContent(array("status" => "success", "exam_id" => $newExam->id));
                }
                
                return $this->response->setJsonContent(array("status" => "failed"));
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
