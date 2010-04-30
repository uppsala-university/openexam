<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/manager.inc
// Author: Anders Lövgren
// Date:   2010-04-27
// 
// Support for managing exams.
// 

class Manager
{
    private $exam;   // Exam ID
    private $info = null;
    
    public function __construct($exam)
    {
	$this->exam = $exam;
    }
    
    public function getExamID() 
    {
	return $this->exam;
    }
    
    // 
    // Get all exams where this user is the manager for.
    // 
    public static function getExams($user)
    {
	$sql = sprintf("SELECT id AS ExamID, name AS ExamName, descr AS ExamDescription, starttime AS ExamStartTime, 
			          endtime AS ExamEndTime, created AS ExamCreated, updated AS ExamUpdated, creator AS ExamCreator,
			          decoded AS ExamDecoded
			FROM exams
			WHERE creator = '%s'", $user);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);	
    }
    
    // 
    // Get meta information about the exam. This is used by the various teacher script 
    // to evaluate whether an operation like contribute or decode can be performed.
    // 
    public function getInfo($flush = false)
    {
	if($flush) {
	    $this->info = null;    // force update
	}
	if(!isset($this->info)) {
	    $sql = sprintf("SELECT id AS ExamID, starttime AS ExamStartTime, endtime AS ExamEndTime, decoded AS ExamDecoded
			    FROM exams WHERE id = %d", $this->exam);
	    $res = Database::getConnection()->query($sql);
	    if(PEAR::isError($res)) {
		throw new DatabaseException($res->getMessage());
	    }
	    $this->info = new DataRecord($res->fetchRow());
	    
	    if($this->info->getExamDecoded() == 'Y') {
		$this->info->setDecodable(true);         // Can be decoded
		$this->info->setContributable(false);    // Can contribute questions
		$this->info->setExaminatable(false);     // Can add student for examination
		$this->info->setCorrectable(false);      // Can correct answers
	    } else {
		$stime = strtotime($this->info['examstarttime']);
		$etime = strtotime($this->info['examendtime']);
		$ctime = time();

		if($ctime < $stime) {         // Before exam begins
		    $this->info->setDecodable(false);
		    $this->info->setContributable(true);
		    $this->info->setExaminatable(true);
		    $this->info->setCorrectable(false);		    
		} elseif($ctime < $etime) {   // After exam begin, but before its finished
		    $this->info->setDecodable(false);
		    $this->info->setContributable(false);
		    $this->info->setExaminatable(true);
		    $this->info->setCorrectable(false);		    
		} else {                      // After exam has finished
		    if($this->isCorrected()) {
			$this->info->setContributable(false);
			$this->info->setExaminatable(false);
			$this->info->setCorrectable(true);
			$this->info->setDecodable(true);
		    } else {
			$this->info->setContributable(false);
			$this->info->setExaminatable(false);
			$this->info->setCorrectable(true);
			$this->info->setDecodable(false);
		    }
		}
	    }
	}
	return $this->info;
    }
    
    // 
    // Get data for this exam.
    // 
    public function getData()
    {
	$sql = sprintf("SELECT id AS ExamID, name AS ExamName, descr AS ExamDescription, starttime AS ExamStartTime, 
			          endtime AS ExamEndTime, created AS ExamCreated, updated AS ExamUpdated, creator AS ExamCreator,
			          decoded AS ExamDecoded
			FROM exams WHERE id = %d", $this->exam);
        $res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }

    // 
    // Set data for this exam or create a new exam in the database (if exam ID is 0).
    // 
    public function setData($name, $desc, $sdate, $edate)
    {
	$db = Database::getConnection();
	
	if($this->exam != 0) {
	    $sql = sprintf("UPDATE exams SET name = '%s', descr = '%s', starttime = '%s', endtime = '%s'
			     WHERE id = '%d'", 
			   $name, $desc, 
			   date('Y-m-d H:i:s', $sdate), 
			   date('Y-m-d H:i:s', $edate),
			   $this->exam);
	} else {
	    $sql = sprintf("INSERT INTO exams(name, descr, starttime, endtime, created, creator)
	                    VALUES('%s','%s','%s','%s','%s','%s')", 
			   $name, $desc, 
			   date('Y-m-d H:i:s', $sdate), 
			   date('Y-m-d H:i:s', $edate), 
			   date('Y-m-d H:i:s', time()), 
			   phpCAS::getUser());
	}
        $res = $db->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	if($this->exam == 0) {
	    $this->exam = $db->lastInsertId("exams", "id");
	}
    }

    // 
    // Returns true if all answers have been corrected.
    // 
    public function isCorrected()
    {
	// 
	// Get number of answer without a corresponding result record:
	// 
	$sql = sprintf("SELECT COUNT(a.id) FROM students s, answers a 
			LEFT JOIN results r ON a.id = r.answer_id
			WHERE s.exam_id = %d AND s.id = a.student_id AND r.id IS NULL", $this->exam);
	$db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	$row = $res->fetchRow();
	
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return intval($row[0]) == 0;
    }
    
    // 
    // Return true if this user is a contributor for this exam.
    // 
    public function isContributor($user)
    {
        $db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	$sql = sprintf("SELECT COUNT(*) FROM contributors
			 WHERE user = '%s' AND exam_id = %d", $user, $this->exam);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	$row = $res->fetchRow();
    
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return intval($row[0]) >= 1;
    }
    
    // 
    // Get all users granted contributor rights on this exam.
    // 
    public function getContributors()
    {
	$sql = sprintf("SELECT id AS ContributorID, exam_id AS ExamID, user AS ContributorUser
			FROM contributors WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }

    // 
    // Get all users granted examinator rights on this exam.
    // 
    public function getExaminators()
    {
	$sql = sprintf("SELECT id AS ExaminatorID, exam_id AS ExamID, user AS ExaminatorUser
			FROM examinators WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }

    // 
    // Get all users granted decoder rights on this exam.
    // 
    public function getDecoders()
    {
	$sql = sprintf("SELECT id AS DecoderID, exam_id AS ExamID, user AS DecoderUser
			FROM decoders WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
    
    // 
    // Get all questions associated with this exam.
    // 
    public function getQuestions()
    {
	$sql = sprintf("SELECT id AS QuestionID, exam_id AS ExamID, score AS QuestionScore,
			       name AS QuestionName, quest AS QuestionText, user AS QuestionPublisher,
			       video AS QuestionVideo, image AS QuestionImage, audio AS QuestionAudio,
			       type AS QuestionType
			FROM questions WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);			
    }
    
    // 
    // Get the list of students assigned to this exam.
    // 
    public function getStudents()
    {
	$sql = sprintf("SELECT id AS StudentID, exam_id AS ExamID, user AS StudentUser, code AS StudentCode
			FROM students WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);			
    }

    // 
    // These function are used for granting and revoking permissions on this exam.
    // 
   
    private function grantExamRole($user, $role)
    {
	$sql = sprintf("INSERT INTO ${role}s(exam_id, user) VALUES(%d, '%s')", $this->exam, $user);
        $res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }

    private function revokeExamRole($user, $role)
    {
	$sql = sprintf("DELETE FROM ${role}s WHERE exam_id = %d AND id = '%s'", $this->exam, $user);
        $res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    public function addContributor($user)
    {
	$this->grantExamRole($user, "contributor");
    }

    public function deleteContributor($user)
    {
	$this->revokeExamRole($user, "contributor");
    }

    public function addExaminator($user)
    {
	$this->grantExamRole($user, "examinator");
    }

    public function deleteExaminator($user)
    {
	$this->revokeExamRole($user, "examinator");
    }

    public function addDecoder($user)
    {
	$this->grantExamRole($user, "decoder");
    }

    public function deleteDecoder($user)
    {
	$this->revokeExamRole($user, "decoder");
    }

    // 
    // This function creates a clone of this exam and returns the clone object.
    // 
    public function copy()
    {	
        $db = Database::getConnection();
        if($db->supports('transaction')) {	
	    $db->beginTransaction();
	}
	
	// 
	// Create the clone object.
	// 
	$data = $this->getData();
	$copy = new Manager(0);
	$copy->setData($data->getExamName(), $data->getExamDescription(), strtotime($data->getExamStartTime()), strtotime($data->getExamEndTime()));
	
	// 
	// Duplicate the assigned contributor, examinator and decoder roles. 
	// 
	foreach(array( "decoders", "examinators", "contributors" ) as $table) {
	    $sql = sprintf("INSERT INTO %s(exam_id, user) SELECT %d, user FROM %s WHERE id = %d", 
			   $table, $copy->getExamID(), 
			   $table, $this->getExamID());
	    $res = $db->query($sql);
	    if(PEAR::isError($res)) {
		if($db->in_transaction) {
		    $db->rollback();
		}
		throw new DatabaseException($res->getMessage());
	    }
	}
	
	// 
	// Copy all questions, but without any associated answers.
	// 
	$sql = sprintf("INSERT INTO questions(exam_id, score, name, quest, user, video, image, audio, type)
	                SELECT %d, score, name, quest, user, video, image, audio, type
			FROM questions WHERE exam_id = %d", $copy->getExamID(), $this->getExamID());
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    if($db->in_transaction) {
		$db->rollback();
	    }
	    throw new DatabaseException($res->getMessage());
	}
	
        if($db->in_transaction) {
	    $db->commit();
	}
	
	return $copy;
    }

    // 
    // Delete this exam. Referental integrity should ensure that this function fails 
    // if questions have answers or if exam has students.
    // 
    public function delete()
    {
        $db = Database::getConnection();
        if($db->supports('transaction')) {
	    $db->beginTransaction();
	} else {
	    throw new DatabaseException("The exam can't be safely deleted");
	}

	$sql = sprintf("DELETE FROM questions WHERE exam_id = %d", $this->exam);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    if($db->in_transaction) {
		$db->rollback();
	    }
	    throw new DatabaseException($res->getMessage());
	}

	$sql = sprintf("DELETE FROM exams WHERE id = %d", $this->exam);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    if($db->in_transaction) {
		$db->rollback();
	    }
	    throw new DatabaseException($res->getMessage());
	}
	
        if($db->in_transaction) {
	    $db->commit();
	}
    }
    
}

?>
