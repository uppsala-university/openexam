<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/manager.inc
// Author: Anders Lövgren
// Date:   2010-04-27
// 
// A class for making test cases out of existing examiniations. The test case 
// is meant to be used to provide a way for users to test their examinations
// seeing the same interface as the real examination participients, including
// the possibility to answer questions.
// 
// The test case has these characteristics:
// 
//   1. Only the test case creator is registered on examination.
//   2. No existing contributor is added or duplicated.
//   3. The test case can be interrupted (finished) at any time.
// 
// The test case can be corrected and decoded.
// 

class TestCase
{
    private $exam;    // The exam ID.
    
    // 
    // Construct the test case object.
    // 
    public function __construct($exam) 
    {
	$this->exam = $exam;
    }
    
    public function getExamID() 
    {
	return $this->exam;
    }

    // 
    // Create a test case for current exam ID. Upon returning, the exam ID field
    // has been modified to identify the new test case examination.
    // 
    public function create()
    {
	$orig = new Manager($this->exam);
	$copy = $orig->copy(true);	
	$this->exam = $copy->getExamID();
    }
    
    // 
    // Delete this test case.
    // 
    public function delete()
    {
	$manager = new Manager($this->exam);
	$manager->delete(true);
    }

    // 
    // Mark this test case as finished. This ends the examination and allows
    // the user to correct anwsers and decode the exam.
    // 
    public function finish()
    {
	$sql = sprintf("UPDATE exams SET endtime = '%s' WHERE id = %d",
		       date('Y-m-d G:i:s'), $this->exam);
        $res = Database::getConnection()->query($sql);
	if(PEAR::isError($res)) {
	    throw new DatabaseException($res->getMessage());
	}
    }
}

?>
