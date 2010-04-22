<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/index.php
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// The main entry point for the web application.
// 

// 
// System check:
// 
if(!file_exists("../conf/database.conf")) {
    header("location: admin/setup.php?reason=database");
}
if(!file_exists("../conf/config.inc")) {
    header("location: admin/setup.php?reason=config");
}

// 
// If logon is true, then CAS logon is enforced for this page.
// 
$GLOBALS['logon'] = true;

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon, user interface and support for error reporting:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include bussiness logic:
// 
include "include/exam.inc";

// 
// This class implements a basic page.
// 
class ExaminationPage extends BasePage
{
    // 
    // All possible request parameters should be added here along with
    // the regex pattern to validate its value against.
    // 
    private $params = array( "exam"     => "/^\d+$/",
			     "question" => "/^\d+$/",
			     "answer"   => "/.*$/" );
    
    // 
    // Construct the template page.
    // 
    public function __construct()
    {	
	parent::__construct(_("Examination:"));   // Internationalized with GNU gettext
    }

    // 
    // The template page body.
    // 
    public function printBody()
    {
	// 
	// Authorization first:
	// 
	if(isset($_REQUEST['exam'])) {
	    self::checkExaminationAccess($_REQUEST['exam']);
	    if(isset($_REQUEST['question'])) {
		self::checkQuestionAccess($_REQUEST['exam'], $_REQUEST['question']);
	    }
	}
	
	// 
	// Bussiness logic:
	// 
	if(!isset($_REQUEST['exam'])) {
	    self::showAvailableExams();
	} else {
	    if(!isset($_REQUEST['question'])) {
		self::showInstructions($_REQUEST['exam']);
	    } elseif(isset($_REQUEST['answer'])) {
		self::saveQuestion($_REQUEST['exam'], $_REQUEST['question'], $_REQUEST['answer']);
	    } else {
		self::showQuestion($_REQUEST['exam'], $_REQUEST['question']);
	    }
	}
    }
    
    public function printMenu()
    {
	if(isset($_REQUEST['exam'])) {
	
	    $questions = Exam::getQuestions($_REQUEST['exam']);
	    $answers = Exam::getAnswers($_REQUEST['exam'], phpCAS::getUser());

	    // 
	    // Build the associative array of questions and answers. We are going to need
	    // this array for proper sectioning of answered/unanswered questions.
	    // 
	    // $array = array( "q" => array( ... ), "a" => array( ... );
	    // 
	    $menuitem = array();	    
	    foreach($questions as $question) {
		$answered = false;
		foreach($answers as $answer) {
		    if($question->getQuestionID() == $answer->getQuestionID()) {
			$menuitem['a'][] = $question;
			$answered = true;
			break;
		    }
		}
		if(!$answered) {
		    $menuitem['q'][] = $question;
		}
	    }
	    
	    echo "<span id=\"menuhead\">" . _("Questions:") . "</span>\n";
	    echo "<ul>\n";
	    if(isset($menuitem['q'])) {
		foreach($menuitem['q'] as $question) {
		    printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01fp]</a></li>\n",
			   $question->getExamID(),
			   $question->getQuestionID(),
			   utf8_decode($question->getQuestionText()),
			   utf8_decode($question->getQuestionName()),
			   $question->getQuestionScore());
		}
	    }
	    echo "</ul>\n";

	    echo "<span id=\"menuhead\">" . _("Answered:") . "</span>\n";
	    echo "<ul>\n";
	    if(isset($menuitem['a'])) {
		foreach($menuitem['a'] as $question) {
		    printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01f]</a></li>\n",
			   $question->getExamID(),
			   $question->getQuestionID(),
			   utf8_decode($question->getQuestionText()),
			   utf8_decode($question->getQuestionName()),
			   $question->getQuestionScore());
		}
	    }
	    echo "</ul>\n";
	}
    }
    
    // 
    // Check that caller is authorized to access this exam.
    // 
    private function checkExaminationAccess($exam)
    {
	$data = Exam::getExamData(phpCAS::getUser(), $exam);
	if(!$data->hasExamID()) {
	    ErrorPage::show(_("Active examination was not found!"),
			    sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
	    exit(1);
	}
	
	$now = time();
	$stime = strtotime($data->getExamStartTime());
	$etime = strtotime($data->getExamEndTime());
	
	if(!($stime <= $now && $now <= $etime)) {
	    ErrorPage::show(_("Active examination was not found!"),
			    sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
	    exit(1);
	}
    }

    // 
    // Check that the requested question is part of this exam.
    // 
    private function checkQuestionAccess($exam, $question)
    {
	$data = Exam::getQuestionData($question);
	if(!$data->hasQuestionID()) {
	    ErrorPage::show(_("Request parameter error!"),
			    sprintf("<p>" . _("No question data was found for the requested question. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
	    exit(1);
	}
	if($data->getExamID() != $exam) {
	    ErrorPage::show(_("Request parameter error!"),
			    sprintf("<p>" . _("The requested question is not related to the requested examination. This should not occure unless the request parameters has been explicit temperered.") . "</p>"));
	    exit(1);
	}
    }
    
    // 
    // Show available exams. It's quite possible that no exams has been approved for the user.
    // 
    private function showAvailableExams() 
    {
	$exams = Exam::getActiveExams(phpCAS::getUser());
	
	if($exams->count() == 0) {
	    ErrorPage::show(_("Active examination was not found!"),
			    sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
	    exit(1);
	}
	
	printf("<h3>" . _("Select the examination") . "</h3>\n");
	if($exams->count() > 1) {
	    printf("<p>" . _("You have been assigned multiple examinations. Select the one to take by clicking on the examinations 'Begin' button.") . "</p>\n");
	}
	
	printf("<p>" . _("These examinations have been assigned to you, click on the button next to the description to begin the examination.") . "</p>\n");
	foreach($exams as $exam) {
	    printf("<div class=\"examination\">\n");
	    printf("<div class=\"examhead\">%s</div>\n", utf8_decode($exam->getExamName()));
	    printf("<div class=\"exambody\">%s<p>%s: <b>%s</b></p>\n", 
		   utf8_decode(str_replace("\n", "<br>", $exam->getExamDescription())),
		   _("The examination ends"), 
		   strftime(TIME_FORMAT, strtotime($exam->getExamEndTime())));
	    printf("<form action=\"exam.php\" method=\"GET\">\n");
	    printf("<input type=\"hidden\" name=\"exam\" value=\"%d\">\n", $exam->getExamID());
	    printf("<input type=\"submit\" value=\"%s\">\n", _("Begin"));
	    printf("</form>\n");
	    printf("</div>\n");
	    printf("</div>\n");
	}
    }
    
    // 
    // Show some simple instructions on how to doing the exam, along with
    // information about the selected exam.
    // 
    private function showInstructions($exam) 
    {
	$exam = Exam::getExamData(phpCAS::getUser(), $exam);
	if(!$exam->hasExamID()) {
	    ErrorPage::show(_("Active examination was not found!"),
			    sprintf("<p>" . _("The system could not found any active examiniations assigned to your logon ID. If you think this is an error, please contact the examinator for further assistance.") . "</p>"));
	    exit(1);
	}
	
	printf("<h3>%s</h3>\n", utf8_decode($exam->getExamName()));
	printf("<p>" . _("In the left side menu are all questions that belongs to this examination. Questions already answered will appear under the 'Answered' section. The number within paranthesis is the score for each question.") . "</p>\n");
	printf("<p>" . _("Remember to <u>press the save button</u> when you have <u>answered a question</u>, and before moving on to another one. It's OK to save the answer to a question multiple times. Logout from the examination when you are finished.") . "</p>\n");
	printf("<p>" . _("Good luck!") . "</p>\n");
    }
    
    // 
    // Show the selected question.
    // 
    private function showQuestion($exam, $question)
    {
	$qdata = Exam::getQuestionData($question);
	$adata = Exam::getAnswerData($question, phpCAS::getUser());
	
	printf("<h3>%s %s [%.01fp]</h3>\n", _("Question"), 
	       utf8_decode($qdata->getQuestionName()), $qdata->getQuestionScore());
	printf("<p><div class=\"examination\">%s</div></p>\n", 
	       str_replace("\n", "<br>", $qdata->getQuestionText()));
	
	printf("<p>" . _("Answer:") . "</p>\n");
	printf("<form action=\"exam.php\" method=\"GET\">\n"); 
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam);
	printf("<input type=\"hidden\" name=\"question\" value=\"%d\" />\n", $question);
	printf("<textarea name=\"answer\" cols=\"100\" rows=\"10\">%s</textarea>\n", utf8_decode($adata->getAnswerText()));
	printf("<br /><br />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Save"));
	printf("</form>\n");
    }
    
    // 
    // Save the answer for an question.
    // 
    private function saveQuestion($exam, $question, $answer)
    {
	Exam::setAnswer($exam, $question, phpCAS::getUser(), utf8_encode($answer));
	header(sprintf("location: exam.php?exam=%d&question=%d&status=ok", $exam, $question));
    }
    
    // 
    // Validates request parameters.
    // 
    public function validate()
    {
	foreach($this->params as $param => $pattern) {
	    if(isset($_REQUEST[$param])) {
		if(!preg_match($pattern, $_REQUEST[$param])) {
		    ErrorPage::show(_("Request parameter error!"),
				    sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
					    $param, $pattern));
		    exit(1);
		}
	    }
	}
    }
}

// 
// Validate request parameters and (if validate succeeds) render the page.
// 
$page = new ExaminationPage();
$page->validate();
$page->render();

?>
