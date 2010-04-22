<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/exam.inc
// Author: Anders Lövgren
// Date:   2010-04-21

// 
// Support class for the online examination.
// 

class Exam
{
    // 
    // Get all exams that has been assigned to this user and is active right now.
    // 
    public function getActiveExams($user) 
    {
	$now = date('Y-m-d H:i:s');
	$sql = sprintf("SELECT e.id AS ExamID, s.id AS StudentID, e.name AS ExamName, e.descr AS ExamDescription, 
			       e.starttime AS ExamStartTime, e.endtime AS ExamEndTime, e.created AS ExamCreated,
			       e.updated AS ExamUpdated, e.creator AS ExamCreator
			FROM students s, exams e
			WHERE s.user = '%s' AND s.exam_id = e.id AND '%s' BETWEEN e.starttime AND e.endtime",
		       $user, $now);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);	
    }
    
    // 
    // Get data for this exam.
    // 
    public function getExamData($user, $exam) 
    {
	$sql = sprintf("SELECT e.id AS ExamID, s.id AS StudentID, e.name AS ExamName, e.descr AS ExamDescription, 
			       e.starttime AS ExamStartTime, e.endtime AS ExamEndTime, e.created AS ExamCreated,
			       e.updated AS ExamUpdated, e.creator AS ExamCreator
			FROM students s, exams e
			WHERE s.user = '%s' AND s.exam_id = e.id AND e.id = %d", $user, $exam);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }
    
    // 
    // Get all questions for this exam.
    // 
    public function getQuestions($exam) 
    {
	$sql = sprintf("SELECT q.id AS QuestionID, q.exam_id AS ExamID, q.score AS QuestionScore, q.name AS QuestionName,
			       q.quest AS QuestionText, q.user AS QuestionPublisher, q.video AS QuestionVideo,
			       q.image AS QuestionImage, q.audio AS QuestionAudio, q.type AS QuestionType
			FROM questions q WHERE q.exam_id = %d
			ORDER BY QuestionID", $exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }

    // 
    // Get data for an single question.
    // 
    public function getQuestionData($question) 
    {
	$sql = sprintf("SELECT id AS QuestionID, exam_id AS ExamID, score AS QuestionScore, name AS QuestionName,
			       quest AS QuestionText, user AS QuestionPublished, video AS QuestionVideo,
			       image AS QuesionImage, audio AS QuestionAudio, type AS QuestionType
			FROM questions WHERE id = %d", $question);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }
    
    // 
    // Get all answers for the exam for this user.
    // 
    public function getAnswers($exam, $user)
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, a.student_id AS StudentID, a.answer AS AnswerText
			FROM answers a, students s 
			WHERE s.exam_id = %d AND a.student_id = s.id AND s.user = '%s' AND a.student_id = s.id
			ORDER BY QuestionID", $exam, $user);
	// die($sql);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }

    // 
    // Get users answer for a single question.
    // 
    public function getAnswerData($question, $user)
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, a.student_id AS StudentID, a.answer AS AnswerText
		        FROM answers a, students s 
			WHERE a.question_id = %d AND a.student_id = s.id AND s.user = '%s'", $question, $user);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }
    
    // 
    // Update or insert the answer from user to this question.
    // 
    public function setAnswer($exam, $question, $user, $answer)
    {
	$data = self::getAnswerData($question, $user);
	if(!$data->hasAnswerID()) {
	    $uid = self::getStudentID($exam, $user);
	    $sql = sprintf("INSERT INTO answers(question_id, student_id, answer) VALUES(%d, %d, '%s')",
			   $question, $uid, $answer);
	} else {
	    $sql = sprintf("UPDATE answers SET answer = '%s' WHERE id = %d", 
			   $answer, $data->getAnswerID());
	}
	$res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    // 
    // Get the ID of this student.
    // 
    private function getStudentID($exam, $user) 
    {
	$db = Database::getConnection();
	$db->setFetchMode(MDB2_FETCHMODE_ORDERED);
	
	$sql = sprintf("SELECT id FROM students WHERE user = '%s' AND exam_id = %d", $user, $exam);
	$res = $db->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	$row = $res->fetchRow();	
	
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $row[0];
    }
}

?>
