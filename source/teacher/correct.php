<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/correct.php
// Author: Anders Lövgren
// Date:   2010-04-28
// 
// This page is used by teachers for correcting answers to questions on 
// an exam.
// 

// 
// Force logon for unauthenticated users:
// 
$GLOBALS['logon'] = true;

// 
// System check:
// 
if(!file_exists("../../conf/database.conf")) {
    header("location: setup.php?reason=database");
}
if(!file_exists("../../conf/config.inc")) {
    header("location: setup.php?reason=config");
}

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon and user interface support:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/teacher.inc";
include "include/exam.inc";
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";

// 
// Support classes:
// 
include "include/scoreboard.inc";
include "include/html.inc";

// 
// The answer correction page:
// 
class CorrectionPage extends TeacherPage
{
    private $params = array( "exam"     => "/^\d+$/",
			     "answer"   => "/^\d+$/", 
			     "question" => "/^\d+$/",
			     "student"  => "/^\d+$/",
			     "verbose"  => "/^\d+$/",
			     "mode"     => "/^(mark|save)$/" );
    private $verbose = false;
    
    public function __construct()
    {
	parent::__construct(_("Answer Correction Page"), $this->params);	
	if(isset($_REQUEST['verbose'])) {
	    $this->verbose = $_REQUEST['verbose'];
	}
    }

    // 
    // The main entry point. This is where all processing begins.
    // 
    public function printBody()
    {
        //
	// Authorization first:
	//
	if(isset($_REQUEST['exam'])) {
	    self::checkAccess($_REQUEST['exam']);
	}
	
	//
	// Bussiness logic:
	//
	if(isset($_REQUEST['exam'])) {
	    if(isset($_REQUEST['question'])) {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
		    self::assert(array('score', 'comment'));
		    self::saveQuestionScore($_REQUEST['exam'], $_REQUEST['question']);
		} else {
		    self::markQuestionScore($_REQUEST['exam'], $_REQUEST['question']);
		}
	    } elseif(isset($_REQUEST['student'])) {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
 		    self::assert(array('score', 'comment'));
		    self::saveStudentScore($_REQUEST['exam'], $_REQUEST['student']);
		} else {
		    self::markStudentScore($_REQUEST['exam'], $_REQUEST['student']);
		}
	    } elseif(isset($_REQUEST['answer'])) {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
 		    self::assert(array('score', 'comment'));
		    self::saveAnswerScore($_REQUEST['exam'], $_REQUEST['answer']);
		} else {
		    self::markAnswerScore($_REQUEST['exam'], $_REQUEST['answer']);
		}
	    } else {
		if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
		    self::saveScoreBoard($_REQUEST['exam']);
		} else {
		    self::showScoreBoard($_REQUEST['exam']);
		}
	    }
	} else {
	    self::showAvailableExams();
	}
    }
    
    public function printMenu()
    {
    }

    // 
    // verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess($exam)
    {
	$role = "contributor";
	
	if(!Teacher::userHasRole($exam, $role, phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
	    exit(1);
	}
    }
    
    // 
    // Save result from saveQuestionScore(), saveStudentScore() and saveAnswerScore().
    // 
    private function saveAnswerResult($exam) 
    {
    	$results = isset($_REQUEST['result']) ? $_REQUEST['result'] : array();
    	$correct = new Correct($exam);
    	$correct->setAnswerResult($_REQUEST['score'], $_REQUEST['comment'], $results);
    	header(sprintf("location: correct.php?exam=%d", $exam));
    }

    // 
    // Save result posted from saveAnswerScore().
    // 
    private function saveAnswerScore($exam, $answer)
    {
	self::saveAnswerResult($exam);
    }

    // 
    // Save result posted from markStudentScore().
    // 
    private function saveStudentScore($exam, $student)
    {
	self::saveAnswerResult($exam);
    }

    // 
    // Save result from markQuestionScore().
    // 
    private function saveQuestionScore($exam, $question) 
    {
	self::saveAnswerResult($exam);
    }
    
    // 
    // Display the answer to a single question.
    // 
    private function viewQuestionAnswer($question, $answer)
    {
	printf("<tr class=\"nonth\"><td>&nbsp;</td></tr>\n");
	    
	if($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    printf("<tr class=\"question\"><td><u>%s: %s</u><br />%s</td></tr>\n", 
		   _("Question"), 
		   utf8_decode($question->getQuestionName()),
		   utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	} else {
	    $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
	    printf("<tr class=\"question\"><td><u>%s: %s</u><br />%s<br/>%s: %s</td></tr>\n", 
		   _("Question"), 
		   utf8_decode($question->getQuestionName()),
		   utf8_decode(str_replace("\n", "<br>", $qchoice[0])),
		   _("Correct answer"), implode(", ", array_keys($qchoice[1], true)));
	}
	
	printf("<tr class=\"answer\">\n");
	if($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    printf("<td><u>%s</u>:<br />%s</td>", 
		   _("Answer"),
		   utf8_decode(str_replace("\n", "<br>", $answer->getAnswerText())));
	} else {
	    $achoice = Exam::getQuestionChoice($answer->getAnswerText());
	    printf("<td><u>%s</u>:<br />%s</td>", 
		   _("Answer"),
		   utf8_decode(str_replace("\n", "<br>", implode(", ", $achoice[1]))));
	}
	if($answer->hasResultID()) {
	    printf("<input type=\"hidden\" name=\"result[%d]\" value=\"%d\" />",
		   $answer->getAnswerID(), $answer->getResultID());
	}
	if($answer->hasResultScore()) {
	    printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" value=\"%.01f\" size=\"8\" /><br />%s: %.01f</td>",
		   $answer->getAnswerID(), 
		   $answer->getResultScore(),
		   _("Max score"),
		   $question->getQuestionScore());
	} elseif($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" size=\"8\" /><br />%s: %.01f</td>",
		   $answer->getAnswerID(), 
		   _("Max score"),
		   $question->getQuestionScore());
	} else {
	    // 
	    // Compare student answers against the correct answers
	    // 
	    $keys = array_keys($qchoice[1], true);
	    $hits = 0;
	    foreach($keys as $key) {
		if(in_array($key, $achoice[1])) {
		    $hits++;   // Increment on correct answer
		}
	    }
	    foreach($achoice[1] as $key) {
		if(!in_array($key, $keys)) {
		    $hits--;   // Substract for wrong answer
		}
	    }
	    if($hits < 0) {
		$hits = 0;
	    }
	    printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" value=\"%.01f\" size=\"8\" /><br />%s: %.01f</td>",
		   $answer->getAnswerID(), 
		   ($hits / count($keys)) * $question->getQuestionScore(),
		   _("Max score"),
		   $question->getQuestionScore());
	}
	printf("</tr>\n");
	printf("<tr class=\"comment\"><td>%s: <input type=\"text\" name=\"comment[%d]\" value=\"%s\" size=\"95\" title=\"%s\" /><br/ ></td></tr>",
	       _("Comment"),
	       $answer->getAnswerID(), 
	       $answer->hasResultComment() ? utf8_decode($answer->getResultComment()) : "",
	       _("This optional field can be used to save an comment for this answer correction."));
    }
    
    // 
    // Examine (correct) an answer to a single question from this student.
    // 
    private function markAnswerScore($exam_id, $answer_id)
    {
	$correct = new Correct($exam_id);
	$answer = $correct->getQuestionAnswer($answer_id);

	if($answer->getQuestionPublisher() != phpCAS::getUser()) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Correction of answers to this question has been assigned to %s, you are not allowed to continue. The script processing has halted."), $this->getFormatName($answer->getQuestionPublisher())));
	    exit(1);
	}
	
	$exam = new Exam();
	$question = $exam->getQuestionData($answer->getQuestionID());
	
	printf("<h3>" . _("Correct the answer for this question.") . "</h3>\n");
	printf("<form action=\"correct.php\" method=\"POST\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam_id);
	printf("<input type=\"hidden\" name=\"action\" value=\"correct\" />\n");
	printf("<input type=\"hidden\" name=\"answer\" value=\"%d\" />\n", $answer_id);
	printf("<input type=\"hidden\" name=\"mode\" value=\"save\" />\n");
	printf("<table>\n");
	printf("<tr><th>%s</th><th>%s</th>\n", _("Answer"), _("Score"));
	self::viewQuestionAnswer($question, $answer);
	printf("</table>\n");
	printf("<br />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Submit"));
	printf("</form>\n");	
    }
    
    // 
    // Examine all answers from a single student.
    // 
    private function markStudentScore($exam_id, $student_id)
    {
	$correct = new Correct($exam_id);
	$answers = $correct->getStudentAnswers($student_id);
	
	$exam = new Exam();
	
	printf("<h3>" . _("Correct all answers at once for this student examination") . "</h3>\n");
	printf("<p>" . _("Only those questions where this student have given an answer for is shown below. Questions published by other people for this examination is hidden.") . "</p>\n");
	
	if($answers->count() == 0) {
	    printf("<h5>" . _("No Answers Found") . "</h5>\n");
	    printf("<p>"  . _("It appears that this student have not answered any questions at all.") . "</p>\n");
	    return;
	}
	
	$found = false;
	foreach($answers as $answer) {
	    if($answer->getQuestionPublisher() == phpCAS::getUser()) {
		$found = true;
		break;
	    }
	}
	if(!$found) {
	    printf("<h5>" . _("No Answers Found") . "</h5>\n");
	    printf("<p>"  . _("It appears that this student have not answered any of you questions.") . "</p>\n");
	    return;
	}
	
	printf("<form action=\"correct.php\" method=\"POST\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam_id);
	printf("<input type=\"hidden\" name=\"action\" value=\"correct\" />\n");
	printf("<input type=\"hidden\" name=\"student\" value=\"%d\" />\n", $student_id);
	printf("<input type=\"hidden\" name=\"mode\" value=\"save\" />\n");
	printf("<table>\n");
	printf("<tr><th>%s</th><th>%s</th>\n", _("Answer"), _("Score"));
	foreach($answers as $answer) {
	    if($answer->getQuestionPublisher() != phpCAS::getUser()) {
		continue;   // Not publisher of this question.
	    }
	    $question = $exam->getQuestionData($answer->getQuestionID());
	    self::viewQuestionAnswer($question, $answer);
	}
	
	printf("</table>\n");
	printf("<br />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Submit"));
	printf("</form>\n");	
    }

    // 
    // Display the form where caller can set scores and comments for all answers
    // at once to a single question.
    // 
    private function markQuestionScore($exam_id, $question_id)
    {
	$correct = new Correct($exam_id);
	$answers = $correct->getQuestionAnswers($question_id);
	
	$exam = new Exam();
	$question = $exam->getQuestionData($question_id);

	printf("<h3>" . _("Correct multipe answers for the question '%s'") . "</h3>\n",
	       utf8_decode($question->getQuestionName()));
	if($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    printf("<p><u>%s</u>:</p><p>%s</p>", 
		   _("Question"),
		   utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	} else {
	    $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
	    printf("<p><u>%s</u>:</p><p>%s</p><p>%s: %s<br />%s: %s</p>", 
		   _("Question"),
		   utf8_decode(str_replace("\n", "<br>", $qchoice[0])),
		   _("Choices"), implode(", ", array_keys($qchoice[1])),
		   _("Correct answer"), implode(", ", array_keys($qchoice[1], true)));
	}
	printf("<p><u>%s</u>: %.01f</p>", 
	       _("Max score"),
	       $question->getQuestionScore());

	if($answers->count() == 0) {
	    printf("<h5>" . _("No Answers Found") . "</h5>\n");
	    printf("<p>"  . _("It appears that no students have answered this question.") . "</p>\n");
	    return;
	}
	
	printf("<form action=\"correct.php\" method=\"POST\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam_id);
	printf("<input type=\"hidden\" name=\"action\" value=\"correct\" />\n");
	printf("<input type=\"hidden\" name=\"question\" value=\"%d\" />\n", $question_id);
	printf("<input type=\"hidden\" name=\"mode\" value=\"save\" />\n");
	printf("<table>\n");
	printf("<tr><th>%s</th><th>%s</th>\n", _("Answer"), _("Score"));
	foreach($answers as $answer) {
	    self::viewQuestionAnswer($question, $answer);
	}
	printf("</table>\n");
	printf("<br />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Submit"));
	printf("</form>\n");
    }
    
    private function showAvailableExams()
    {
	printf("<h3>" . _("Correct Answers") . "</h3>\n");
	printf("<p>"  . _("Select the examination you wish to correct answers to questions for (applies only to corractable examinations).") . "</p>\n");

	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();
	
	$exams = Correct::getExams(phpCAS::getUser());	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    $child = $root->addChild(utf8_decode($exam->getExamName()));
	    if($manager->getInfo()->isCorrectable()) {
		$child->setLink(sprintf("?exam=%d", $exam->getExamID()),
				_("Click on this link to open this examination to correct answers."));
	    }
	    $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime()))));
	    $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime()))));
	}
	$tree->output();
    }
    
    private function showScoreBoard($exam)
    {
	$manager = new Manager($exam);	
	$data = $manager->getData();
	
	printf("<h3>" . _("Correct Answers") . "</h3>\n");
	printf("<p>" . 
	       _("This table shows all answers from students to questions for the examination '%s'. ") .
	       "</p>\n",
	       utf8_decode($data->getExamName()));
	printf("<p>" . 
	       _("Correct answers by student (rows), by question (column) or individual (by index). ") . 
	       _("You can only correct answers for those questions published by yourself.") . 
	       "</p>\n");	       

	if($this->verbose) {
	    printf("<span class=\"links viewmode\"><a href=\"?exam=4&amp;verbose=0\">%s</a></span>\n", _("Silent"));
	} else {
	    printf("<span class=\"links viewmode\"><a href=\"?exam=4&amp;verbose=1\">%s</a></span>\n", _("Verbose"));
	}

 	$board = new ScoreBoardPrinter($exam);
	$board->setVerbose($this->verbose);
	$board->output();
	
	printf("<h5>" . _("Download Result") . "</h5>\n");
	printf("<p>" . _("Click <a href=\"%s\">here</a> to download the score board.") . "</p>\n", 
	       sprintf("?exam=%d&amp;mode=save", $exam));
	
	printf("<h5>" . _("Color Codes") . "</h5>\n");
	printf("<p>"  . _("These are the color codes used in the score board:") . "</p>\n");
	$codes = array( 
			"ac" => _("Answer has been corrected."),
			"no" => _("This answer should be corrected by another person."),
			"na" => _("No answer was given for this question."),
			"nc" => _("The answer has not yet been corrected.")
			);	
	printf("<table>\n");
	foreach($codes as $code => $desc) {
	    printf("<tr><td class=\"cc %s\">&nbsp;</td><td>%s</td>\n", $code, $desc);
	}
	printf("</table>\n");
    }
    
    private function saveScoreBoard($exam)
    {	
	$manager = new Manager($exam);	
	$data = $manager->getData();
		
 	$board = new ScoreBoard($exam);
	$questions = $board->getQuestions();

	ob_end_clean();

	header("Content-Type: text/tab-separated-values");
	header(sprintf("Content-Disposition: attachment;filename=\"%s.csv\"", str_replace(" ", "_", $data->getExamName())));
	header("Cache-Control: no-cache");
	header("Pragma-directive: no-cache");
	header("Cache-directive: no-cache");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	$i = 1;
	printf("%s", _("Code"));
	foreach($questions as $question) {
	    printf("\tQ%d.", $i++);
	}
	printf("\t%s", _("Score"));
	printf("\t%s", _("Possible"));
	printf("\t%s", _("Max score"));
	printf("\t%s", _("Percent"));
	printf("\t%s\n", _("Grade"));
	// 
	// Output the list of anonymous students.
	// 
	$students = $board->getStudents();
	$grades = new ExamGrades($data->getExamGrades());
	foreach($students as $student) {
	    printf("%s", $student->getStudentCode());
	    foreach($questions as $question) {
		$data = $board->getData($student->getStudentID(), $question->getQuestionID());
		if(!isset($data)) {
		    printf("\t");
		} else {
		    if($data->hasResultScore()) {
			printf("\t%.01f", $data->getResultScore());
		    } else {
			printf("\t");
		    }
		}
	    }
	    $score = $board->getStudentScore($student->getStudentID());
	    $grade = $grades->getGrade($score->getSum());
	    printf("\t%.01f", $score->getSum());
	    printf("\t%.01f", $score->getMax());
	    printf("\t%.01f", $board->getMaximumScore());
	    printf("\t%.01f", 100 * $score->getSum() / $board->getMaximumScore());
	    printf("\t%s", $grade);
	    printf("\n");
	}
	exit(0);
    }
}

$page = new CorrectionPage();
$page->render();

?>
