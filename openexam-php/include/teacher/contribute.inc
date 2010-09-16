<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/contribute.inc
// Author: Anders Lövgren
// Date:   2010-04-26
// 
// Support class for contributing questions for an exam.
// 

class Contribute
{
    private $exam;
    
    public function __construct($exam) 
    {
	$this->exam = $exam;
    }
    
    // 
    // Get all exams where this user is a contributor.
    // 
    public static function getExams($user)
    {
	$sql = sprintf("SELECT e.id AS ExamID, e.name AS ExamName, e.descr AS ExamDescription, 
			       e.starttime AS ExamStartTime, e.endtime AS ExamEndTime, e.created AS ExamCreated,
			       e.updated AS ExamUpdated, e.creator AS ExamCreator
			FROM exams e, contributors c
			WHERE e.id = c.exam_id AND c.user = '%s'
			ORDER BY e.starttime", $user);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new ResultSet($res);
    }

    public function addQuestion($exam, $score, $name, $quest, $type, $user, $video, $audio, $image)
    {
	$sql = sprintf("INSERT INTO questions(exam_id, score, name, quest, type, user, video, audio, image)
	                VALUES(%d, %f, '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
		       $exam, $score, $name, $quest, $type, $user, $video, $audio, $image);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }

    public function editQuestion($question, $exam, $score, $name, $quest, $type, $user, $video, $audio, $image)
    {
	$sql = sprintf("UPDATE questions SET exam_id = %d, score = %f, name = '%s', quest = '%s', type = '%s', 
			                     user = '%s', video = '%s', audio = '%s', image = '%s'
			WHERE id = %d",
		       $exam, $score, $name, $quest, $type, $user, $video, $audio, $image, $question);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    public function deleteQuestion($question)
    {
	$sql = sprintf("DELETE FROM questions WHERE id = %d", $question);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    public function deleteQuestions()
    {
	$sql = sprintf("DELETE FROM questions WHERE exam_id = %d", $this->exam);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    public function removeQuestion($question, $comment)
    {
	$sql = sprintf("UPDATE questions SET status = 'removed', comment = '%s'
			WHERE id = %d AND exam_id = %d", $comment, $question, $this->exam);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
    
    public function restoreQuestion($question)
    {
	$sql = sprintf("UPDATE questions SET status = 'active', comment = NULL
			WHERE id = %d AND exam_id = %d", $question, $this->exam);
	$res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
}

?>
