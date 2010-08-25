<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/exam/index.php
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// This is the page where students do their exam.
// 

// 
// Enable autosave if non-zero. Try to set session length twice the value of
// the autosave interval. This should affect the CAS logon session length.
// 
if(!defined("SESSION_AUTOSAVE")) {
    define ("SESSION_AUTOSAVE", 0);
}
if(!defined("SESSION_LIFETIME")) {
    define ("SESSION_LIFETIME", 2 * SESSION_AUTOSAVE);
}

// 
// System check:
// 
if(!file_exists("../../conf/database.conf")) {
    header("location: admin/setup.php?reason=database");
}
if(!file_exists("../../conf/config.inc")) {
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
include "include/html.inc";

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
include "include/mplayer.inc";

// 
// Needed to bypass access checks for contributors (in preview mode):
// 
include "include/teacher/manager.inc";

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
			     "question" => "/^(\d+|all)$/" );
    private $author = false;
    
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
	    if(isset($_REQUEST['question']) && $_REQUEST['question'] != "all") {
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
	    } elseif($_REQUEST['question'] == "all") {
		self::showQuestions($_REQUEST['exam']);
	    } elseif(isset($_REQUEST['answer'])) {
		self::saveQuestion($_REQUEST['exam'], $_REQUEST['question'], $_REQUEST['answer']);
	    } else {
		self::showQuestion($_REQUEST['exam'], $_REQUEST['question']);
	    }
	}
	
	if($this->testcase) {
	    printf("<hr/>\n");
	    printf("<b>" . ("Test case") .":</b> <a href=\"%s\" title=\"%s\">%s</a> <a href=\"%s\" title=\"%s\">%s</a>\n",
		   sprintf("../teacher/manager.php?exam=%d&amp;action=finish", $_REQUEST['exam']),
		   _("Stops the examination and allow you to correct and decode results."),
		   _("Finish"),
		   sprintf("../teacher/manager.php?exam=%d&amp;action=cancel", $_REQUEST['exam']),
		   _("Delete this test case and return to the examination manager."),
		   _("Cancel"));
	}
    }
    
    public function printMenu()
    {
	if(isset($_REQUEST['exam'])) {
	    
	    $menuitem = self::getQuestions();
	    
	    if(isset($menuitem['q'])) {
		echo "<span id=\"menuhead\">" . _("Questions:") . "</span>\n";
		echo "<ul>\n";
		foreach($menuitem['q'] as $question) {
		    if($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
			$options = Exam::getQuestionChoice($question->getQuestionText());
			$question->setQuestionText($options[0]);
		    }
		    printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01fp]</a></li>\n",
			   $question->getExamID(),
			   $question->getQuestionID(),
			   utf8_decode(strip_tags($question->getQuestionText())),
			   utf8_decode(strip_tags($question->getQuestionName())),
			   $question->getQuestionScore());
		}
		echo "</ul>\n";
	    }

	    if(isset($menuitem['a'])) {
		echo "<span id=\"menuhead\">" . _("Answered") . ":</span>\n";
		echo "<ul>\n";
		foreach($menuitem['a'] as $question) {
		    if($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
			$options = Exam::getQuestionChoice($question->getQuestionText());
			$question->setQuestionText($options[0]);
		    }
		    printf("<li><a href=\"?exam=%d&amp;question=%d\" title=\"%s\">%s [%.01f]</a></li>\n",
			   $question->getExamID(),
			   $question->getQuestionID(),
			   utf8_decode(strip_tags($question->getQuestionText())),
			   utf8_decode(strip_tags($question->getQuestionName())),
			   $question->getQuestionScore());
		}
		echo "</ul>\n";
	    }

	    echo "<span id=\"menuhead\">" . _("Show") . ":</span>\n";
	    echo "<ul>\n";
	    printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n",
		   $_REQUEST['exam'],
		   _("Show the start page for this examination"), _("Start page"));
	    printf("<li><a href=\"?exam=%d&amp;question=all\" title=\"%s\">%s</a></li>\n",
		   $_REQUEST['exam'],
		   _("Show all questions at the same time"),
		   _("All questions"));
	    echo "</ul>\n";
	}
    }
    
    // 
    // Check that caller is authorized to access this exam.
    // 
    private function checkExaminationAccess($exam)
    {
	// 
	// Allow contributors to bypass normal user checks (for previewing questions).
	// 
	$manager = new Manager($exam);
	$this->author = $manager->isContributor(phpCAS::getUser());
	if($this->author) {
	    $this->testcase = false;
	    return;
	}
	
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
	    ErrorPage::show(_("This examination is now closed!"),
			    sprintf("<p>" . _("This examination ended %s and is now closed. If you think this is an error, please contact the examinator for further assistance.") . "</p>", strftime(DATETIME_FORMAT, $etime)));
	    exit(1);
	}

	$this->testcase = $data->getExamTestCase() == 'Y';
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
		   strftime(DATETIME_ISO, strtotime($exam->getExamEndTime())));
	    
	    $form = new Form("index.php", "GET");
	    $form->addHidden("exam", $exam->getExamID());
	    $form->addSubmitButton("submit", _("Begin"));
	    $form->output();
	    
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
	
	if($this->testcase) {
	    printf("<div class=\"testcase\">\n");
	    printf("<h4>" . _("Running in test case mode!") . "</h4>\n");
	    printf("<p>"  . _("This examination is running in test case mode. This mode allows you to review and work with the examination in the same way as the students will.") . "</p>\n");
	    printf("<p>"  . _("To exit, click either on the 'finish' or 'cancel' link at bottom of the page. Clicking 'finish' will stop the examination and allow you to correct and decode results, while 'cancel' will delete this test case.") . "</p>\n");
	    printf("<h6>" . _("Important") . ":</h6>\n");
	    printf("<p>"  . _("Running in test case mode is non-destructive. The original examination remains unaffected as you are working entierly on a copy of it.") . "</p>\n");
	    printf("</div>\n");
	}	
    }
    
    // 
    // Show all questions at once.
    // 
    private function showQuestions($exam)
    {
	$questions = Exam::getQuestions($exam);
	
	printf("<h3>" . _("Overview of all questions (no answers included)") . "</h3>\n");
	foreach($questions as $question) {
	    if($question->getQuestionType() != QUESTION_TYPE_FREETEXT) {
		$options = Exam::getQuestionChoice($question->getQuestionText());
		$question->setQuestionText($options[0]);		
	    }
	    printf("<h5>%s: %s</h5><p>%s</p><p><a href=\"?exam=%d&amp;question=%d\">[%s]</a></p>\n", 
		   _("Question"),
		   utf8_decode($question->getQuestionName()),
		   utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())),
		   $question->getExamID(), 
		   $question->getQuestionID(),
		   _("Answer"));
	}
    }
    
    // 
    // Show the selected question.
    // 
    private function showQuestion($exam, $question)
    {
	$qdata = Exam::getQuestionData($question);
	$adata = Exam::getAnswerData($question, phpCAS::getUser());

	// 
	// Use custom CSS depending on whether displaying media or not.
	// 
	printf("<style type=\"text/css\">\n");
	if($qdata->hasQuestionVideo() || $qdata->hasQuestionAudio() || $qdata->hasQuestionImage()) {
	    $qdata->setQuestionMedia(true);
	    include "../css/multimedia.css";  // Inline CSS
	} else {
	    printf("textarea.answer { width: 755px; height: 230px; }\n");
	}
	printf("</style>\n");

	printf("<div class=\"left\">\n");
	printf("<h3>%s %s [%.01fp]</h3>\n", _("Question"), 
	       utf8_decode($qdata->getQuestionName()), $qdata->getQuestionScore());

	if($qdata->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    printf("<div class=\"question\">%s</div>\n",
		   utf8_decode(str_replace("\n", "<br>", $qdata->getQuestionText())));
	} else {
	    $options = Exam::getQuestionChoice($qdata->getQuestionText());
	    printf("<div class=\"question\">%s</div>\n", 
		   utf8_decode(str_replace("\n", "<br>", $options[0])));
	}
	
	printf("<div class=\"answer\">\n");
	printf("<p class=\"answer\">" . _("Answer:") . "</p>\n");

	// 
	// Output the question form including any already given answer:
	// 
	$form = new Form("index.php", "POST");
	$form->setId("answerform");
	if(SESSION_AUTOSAVE != 0) {
	    $form->addHidden("autosave", false);
	}
	$form->addHidden("exam", $exam);
	$form->addHidden("question", $question);
	
	if($qdata->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    $input = $form->addTextArea("answer", utf8_decode($adata->getAnswerText()));
	    $input->setClass("answer");
	} elseif($qdata->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE) {
	    $options = Exam::getQuestionChoice($qdata->getQuestionText());
	    $answers = Exam::getQuestionChoice($adata->getAnswerText());
	    foreach($options[1] as $option) {
		$input = $form->addRadioButton("answer[]", utf8_decode($option), utf8_decode($option));
		if(in_array($option, $answers[1])) {
		    $input->setChecked();
	    	}
		$form->addSpace();
	    }
	} elseif($qdata->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
	    $options = Exam::getQuestionChoice($qdata->getQuestionText());
	    $answers = Exam::getQuestionChoice($adata->getAnswerText());
	    foreach($options[1] as $option) {
		$input = $form->addCheckBox("answer[]", utf8_decode($option), utf8_decode($option));
		if(in_array($option, $answers[1])) {
		    $input->setChecked();
		}
		$form->addSpace();
	    }
	}
	if(!$this->author) {
	    $form->addSpace();
	    $button = $form->addSubmitButton("save", _("Save"));
	    $button->setTitle(_("Save your answer in the database."));
	    $button = $form->addSubmitButton("next", _("OK"));
	    $button->setTitle(_("Save and move on to next unanswered question."));
	}
	$form->output();
	
	printf("<script type=\"text/javascript\">\n");
	if(SESSION_AUTOSAVE != 0) {
	    printf("autosave_form('answerform', %d, true);\n", SESSION_AUTOSAVE);
	}
	printf("</script>\n");
	printf("</div>\n");
	if($this->author) {
	    printf("<br/><img src=\"icons/nuvola/info.png\" />\n%s: <i>%s</i>\n", 
		   _("Notice"), _("This question is viewed in preview mode (for question author)."));
	}
	if(isset($_REQUEST['status']) && $_REQUEST['status'] == "ok") {
	    printf("<p><img src=\"icons/nuvola/info.png\" /> " . _("Your answer has been successful saved in the database.") . "</p>\n");
	}
	printf("</div>\n");
	
	if($qdata->hasQuestionMedia()) {
	    printf("<div class=\"right\">\n");
	    if($qdata->hasQuestionVideo()) {
		printf("<div class=\"media\">\n");
		printf("<h3>%s:</h3>\n", _("Video"));
		$videoplayer = MediaPlayerFactory::createMediaPlayer($qdata->getQuestionVideo());
		$videoplayer->output();
		printf("</div>\n");
	    }
	    if($qdata->hasQuestionAudio()) {
		printf("<div class=\"media\">\n");
		printf("<h3>%s:</h3>\n", _("Audio"));
		$audioplayer = MediaPlayerFactory::createMediaPlayer($qdata->getQuestionAudio());
		$audioplayer->output();
		printf("</div>\n");
	    }
	    if($qdata->hasQuestionImage()) {
		printf("<div class=\"media\">\n");
		printf("<h3>%s:</h3>\n", _("Image"));
		printf("<img src=\"%s\" class=\"media\" />\n", $qdata->getQuestionImage());
		printf("</div>\n");
	    }
	    printf("</div>\n");
	}
	printf("<br style=\"clear: both;\">\n");
    }
        
    // 
    // Save the answer for an question.
    // 
    private function saveQuestion($exam, $question, $answer)
    {
	if(is_array($answer)) {
	    $answer = json_encode($answer);
	}
	Exam::setAnswer($exam, $question, phpCAS::getUser(), utf8_encode($answer));
	if(isset($_REQUEST['save'])) {
	    header(sprintf("location: index.php?exam=%d&question=%d&status=ok", $exam, $question));
	} elseif(isset($_REQUEST['next'])) {
	    $menuitem = self::getQuestions();
	    if(count($menuitem['q']) != 0) {
		$next = $menuitem['q'][0];
		$question = $next->getQuestionID();
	    }
	    header(sprintf("location: index.php?exam=%d&question=%d&status=ok", $exam, $question));
	}
    }

    // 
    // Get questions classified as remaining or already answered.
    // 
    private function getQuestions()
    {
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
	return $menuitem;
    }
    
    // 
    // Validates request parameters.
    // 
    public function validate()
    {
	foreach($this->params as $param => $pattern) {
	    if(isset($_REQUEST[$param])) {
		if(is_array($_REQUEST[$param])) {
		    foreach($_REQUEST[$param] as $value) {
			if(!preg_match($pattern, $value)) {
			    ErrorPage::show(_("Request parameter error!"),
					    sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."),
						    $param, $pattern));
			    exit(1);
			}
		    }
		} elseif(!preg_match($pattern, $_REQUEST[$param])) {
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
