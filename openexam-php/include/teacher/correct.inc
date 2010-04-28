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
}

?>
