<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/correct.inc
// Author: Anders Lövgren
// Date:   2010-04-28
// 
// Support for correcting exams.
// 

class Correct
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
	return new ResultSet($res);
    }
    
    // 
    // Get data for a single answer.
    // 
    public function getQuestionAnswer($answer)
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, a.student_id AS StudentID, r.id AS ResultID,
			       a.answer AS AnswerText, a.comment AS AnswerComment, r.score AS ResultScore, r.comment AS ResultComment,
			       s.user AS StudentUser, s.code AS StudentCode, q.user AS QuestionPublisher
			FROM questions q, students s, answers a LEFT JOIN results r ON a.id = r.answer_id
			WHERE s.id = a.student_id AND a.question_id = q.id AND a.id = %d", $answer);
        $res = Database::getConnection()->query($sql);
 	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
	return new DataRecord($res->fetchRow());
    }
    
    // 
    // Get all answers and possibly the result (previous done correction) for 
    // this particular question in current exam.
    // 
    public function getQuestionAnswers($question)
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, a.student_id AS StudentID, r.id AS ResultID, s.exam_id AS ExamID, 
			       a.answer AS AnswerText, a.comment AS AnswerComment, r.score AS ResultScore, r.comment AS ResultComment, 
			       s.user AS StudentUser, s.code AS StudentCode
			FROM students s, answers a LEFT JOIN results r ON a.id = r.answer_id 
			WHERE s.id = a.student_id AND a.question_id = %d AND s.exam_id = %d", 
		       $question, $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
    
    // 
    // Get all answers and possibly result for this student in the current exam. The returned 
    // result will also contain answers to questions where caller is not the publisher (these
    // should be filtered if required).
    // 
    public function getStudentAnswers($student)
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, a.student_id AS StudentID, r.id AS ResultID, s.exam_id AS ExamID, 
			       a.answer AS AnswerText, a.comment AS AnswerComment, r.score AS ResultScore, r.comment AS ResultComment, 
			       s.user AS StudentUser, s.code AS StudentCode, q.user AS QuestionPublisher
			FROM questions q, students s, answers a 
			LEFT JOIN results r ON a.id = r.answer_id 
			WHERE s.id = a.student_id AND s.id = %d AND a.question_id = q.id AND q.exam_id = %d
			ORDER BY a.question_id",
		       $student, $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
    
    // 
    // Save result for answers. The arguments are hashed arrays where the key is
    // the answer ID. The result array is an hashed array where the keys are the
    // answer ID and the values are the result ID.
    // 
    public function setAnswerResult($scores, $comments, $results)
    {
	$db = Database::getConnection();
	foreach(array_keys($scores) as $answer) {
	    if(isset($results[$answer])) {
		$sql = sprintf("UPDATE results SET answer_id = %d, score = %f, comment = '%s'
				WHERE id = %d",
			       $answer, $scores[$answer], utf8_encode($comments[$answer]), $results[$answer]);
	    } else { 
		if($scores[$answer] == "") {
		    continue;   // Don't save uncorrected answers
		}
		    
		$sql = sprintf("INSERT INTO results(answer_id, score, comment) VALUES(%d, %f, '%s')",
			       $answer, $scores[$answer], utf8_encode($comments[$answer]));
	    }
	    $res = $db->query($sql);
	    if(PEAR::isError($res)) {
		throw new DatabaseException($res->getMessage());
	    }
	}
    }
    
    // 
    // Get the score for this exam. The result will have NULL for answers yet to be 
    // corrected and should contain all data required to build the two-dimensional
    // array that maps user and question to its score.
    // 
    public function getScoreBoard()
    {
	$sql = sprintf("SELECT a.id AS AnswerID, a.question_id AS QuestionID, s.id AS StudentID, s.exam_id AS ExamID, 
			       r.score AS ResultScore, s.user AS StudentUser, s.code AS StudentCode, 
			       q.user AS QuestionPublisher, q.score AS QuestionScore 
		        FROM students s, questions q, answers a LEFT JOIN results r ON a.id = r.answer_id
			WHERE s.exam_id = %d AND s.id = a.student_id AND a.question_id = q.id", $this->exam);
	$res = Database::getConnection()->query($sql);
	return new ResultSet($res);
    }
}

// 
// This class represent the summary and total (max) score for a
// single row or column.
// 
class Score
{
    private $sum;
    private $max;
    
    public function __construct($sum, $max)
    {
	$this->sum = $sum;
	$this->max = $max;
    }
    
    public function getSum() 
    {
	return $this->sum;
    }
    
    public function getMax()
    {
	return $this->max;
    }
    
    public function addSum($value)
    {
	$this->sum += $value;
    }
    
    public function addMax($value)
    {
	$this->max += $value;
    }
}

// 
// This class is an abstraction of the score board for one exam.
// 
class ScoreBoard
{
    private $students;
    private $questions;
    private $scores = array();    // Two dimensional array
    
    public function __construct($exam)
    {
	$this->initilize($exam);
    }
    
    public function getQuestions() 
    {
	return $this->questions;
    }
    
    public function getStudents()
    {
	return $this->students;
    }

    public function getData($student, $question) 
    {
	if(!isset($this->scores[$student][$question])) {
	    return null;
	}
	return $this->scores[$student][$question];
    }
        
    private function initilize($exam)
    {
	$manager = new Manager($exam);
	
	$this->questions = $manager->getQuestions();
	$this->students  = $manager->getStudents();
	
	$correct = new Correct($exam);
	$data = $correct->getScoreBoard();
	
	foreach($data as $record) {
	    $this->scores[$record->getStudentID()][$record->getQuestionID()] = $record;
	}
    }

    // 
    // Utility functions:
    // 
    
    public function getStudentScore($student)
    {
	$score = new Score(0, 0);
	if(isset($this->scores[$student])) {
	    foreach($this->scores[$student] as $question => $record) {
		if($record->getResultScore() != null) {
		    $score->addSum($record->getResultScore());
		}
		$score->addMax($record->getQuestionScore());
	    }
	}
	return $score;
    }

    public function getQuestionScore($question)
    {
	$score = new Score(0, 0);
	foreach($this->scores as $student => $data) {
	    $record = $data[$question];
	    if($record->getResultScore() != null) {
		$score->addSum($record->getResultScore());
	    }
	    $score->addMax($record->getQuestionScore());
	}
	return $score;
    } 
   
    public function getMaximumScore()
    {
	$total = 0;
	foreach($this->questions as $question) {
	    $total += $question->getQuestionScore();
	}
	return $total;
    }    
}

?>
