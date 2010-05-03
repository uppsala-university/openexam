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

// 
// Business logic:
// 
include "include/teacher.inc";
include "include/exam.inc";
include "include/teacher/manager.inc";
include "include/teacher/correct.inc";

// 
// The answer correction page:
// 
class CorrectionPage extends TeacherPage
{
    private $params = array( "exam"     => "/^\d+$/",
			     "answer"   => "/^\d+$/", 
			     "question" => "/^\d+$/",
			     "student"  => "/^\d+$/",
			     "mode"     => "/^(mark|save)$/" );
    
    public function __construct()
    {
	parent::__construct(_("Answer Correction Page"), $this->params);	
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
		self::showScoreBoard($_REQUEST['exam']);
	    }
	} else {
	    self::showAvailableExams();
	}
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
    // Examine (correct) an answer to a single question from this student.
    // 
    private function markAnswerScore($exam_id, $answer_id)
    {
	$correct = new Correct($exam_id);
	$answer = $correct->getQuestionAnswer($answer_id);

	if($answer->getQuestionPublisher() != phpCAS::getUser()) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Correction of answers to this question has been assigned to %s, you are not allowed to continue. The script processing has halted."), $answer->getQuestionPublisher()));
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
	printf("<tr class=\"nonth\"><td>&nbsp;</td></tr>\n");	    
	printf("<tr class=\"question\"><td><u>%s %s</u><br />%s</td></tr>\n", 
	       _("Question"), 
	       utf8_decode($question->getQuestionName()),
	       utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	
	printf("<tr class=\"answer\">\n");
	printf("<td><u>%s</u>:<br />%s</td>", 
	       _("Answer"),
	       utf8_decode(str_replace("\n", "<br>", $answer->getAnswerText())));
	if($answer->hasResultID()) {
	    printf("<input type=\"hidden\" name=\"result[%d]\" value=\"%d\" />",
		   $answer->getAnswerID(), $answer->getResultID());
	}
	printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" value=\"%s\" size=\"8\" /><br />%s: %.01f</td>",
	       $answer->getAnswerID(), 
	       $answer->hasResultScore() ? $answer->getResultScore() : "",
	       _("Max score"),
	       $question->getQuestionScore());
	printf("</tr>\n");
	printf("<tr class=\"comment\"><td>%s: <input type=\"text\" name=\"comment[%d]\" value=\"%s\" size=\"50\" title=\"%s\" /><br/ ></td></tr>",
	       _("Comment"),
	       $answer->getAnswerID(), 
	       $answer->hasResultComment() ? utf8_decode($answer->getResultComment()) : "",
	       _("This optional field can be used to save an comment for this answer correction."));
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
	    printf("<tr class=\"nonth\"><td>&nbsp;</td></tr>\n");
	    
	    $question = $exam->getQuestionData($answer->getQuestionID());
	    printf("<tr class=\"question\"><td><u>%s %s</u><br />%s</td></tr>\n", 
		   _("Question"), 
		   utf8_decode($question->getQuestionName()),
		   utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	    
	    printf("<tr class=\"answer\">\n");
	    printf("<td><u>%s</u>:<br />%s</td>", 
		   _("Answer"),
		   utf8_decode(str_replace("\n", "<br>", $answer->getAnswerText())));
	    if($answer->hasResultID()) {
		printf("<input type=\"hidden\" name=\"result[%d]\" value=\"%d\" />",
		       $answer->getAnswerID(), $answer->getResultID());
	    }
	    printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" value=\"%s\" size=\"8\" /><br />%s: %.01f</td>",
		   $answer->getAnswerID(), 
		   $answer->hasResultScore() ? $answer->getResultScore() : "",
		   _("Max score"),
		   $question->getQuestionScore());
	    printf("</tr>\n");
	    printf("<tr class=\"comment\"><td>%s: <input type=\"text\" name=\"comment[%d]\" value=\"%s\" size=\"50\" title=\"%s\" /><br/ ></td></tr>",
		   _("Comment"),
		   $answer->getAnswerID(), 
		   $answer->hasResultComment() ? utf8_decode($answer->getResultComment()) : "",
		   _("This optional field can be used to save an comment for this answer correction."));
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
	printf("<p><u>%s</u>:</p><p>%s</p>", 
	       _("Question"),
	       utf8_decode(str_replace("\n", "<br>", $question->getQuestionText())));
	printf("<p><u>%s</u>: %.01f</p>", 
	       _("Max score"),
	       $question->getQuestionScore());

	printf("<form action=\"correct.php\" method=\"POST\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $exam_id);
	printf("<input type=\"hidden\" name=\"action\" value=\"correct\" />\n");
	printf("<input type=\"hidden\" name=\"question\" value=\"%d\" />\n", $question_id);
	printf("<input type=\"hidden\" name=\"mode\" value=\"save\" />\n");
	printf("<table>\n");
	printf("<tr><th>%s</th><th>%s</th>\n", _("Answer"), _("Score"));
	foreach($answers as $answer) {
	    printf("<tr class=\"nonth\"><td>&nbsp;</td></tr>\n");
	    printf("<tr class=\"answer\">\n");
	    printf("<td><u>%s</u>:<br />%s</td>", 
		   _("Answer"),
		   utf8_decode(str_replace("\n", "<br>", $answer->getAnswerText())));
	    if($answer->hasResultID()) {
		printf("<input type=\"hidden\" name=\"result[%d]\" value=\"%d\" />",
		       $answer->getAnswerID(), $answer->getResultID());
	    }
	    printf("<td valign=\"top\"><input type=\"text\" name=\"score[%d]\" value=\"%s\" size=\"8\" /><br>%s: %.01f</td>",
		   $answer->getAnswerID(), 
		   $answer->hasResultScore() ? $answer->getResultScore() : "",
		   _("Max score"),
		   $question->getQuestionScore());
	    printf("</tr>\n");
	    printf("<tr class=\"comment\"><td>%s: <input type=\"text\" name=\"comment[%d]\" value=\"%s\" size=\"50\" title=\"%s\" /><br/ ></td></tr>",
		   _("Comment"),
		   $answer->getAnswerID(), 
		   $answer->hasResultComment() ? utf8_decode($answer->getResultComment()) : "",
		   _("This optional field can be used to save an comment for this answer correction."));	    
	}
	printf("</table>\n");
	printf("<br />\n");
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Submit"));
	printf("</form>\n");
    }
    
    private function showAvailableExams()
    {
	printf("<p>"  . _("Select the examination you wish to correct the question answer for.") . "</p>\n");
	
	printf("<ul>\n");
	printf("<li>%s:</li>\n", _("Examinations"));
	$exams = Correct::getExams(phpCAS::getUser());	
	foreach($exams as $exam) {
	    printf("<ul>\n");
	    printf("<li><a href=\"?exam=%d\" title=\"%s\">%s</a></li>\n",
		   $exam->getExamID(), 
		   sprintf("%s\n\n%s", utf8_decode($exam->getExamDescription()), _("Follow this link to open the examination for answer correction.")),
		   utf8_decode($exam->getExamName()));
	    printf("<ul>\n");
	    printf("<li>%s: %s</li>\n", _("Starts"), strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime())));
	    printf("<li>%s: %s</li>\n", _("Ends"), strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime())));
	    printf("</ul>\n");
	    printf("<br />\n");
	    printf("</ul>\n");
	}
	printf("</ul>\n");
    }
    
    private function showScoreBoard($exam)
    {
	$manager = new Manager($exam);	
	$data = $manager->getData();
	printf("<p>" .
	       _("This table shows all questions and students for examination '%s'. ") .
	       _("You can only correct answers to those questions you have published.") . 
	       "</p>\n", 
	       utf8_decode($data->getExamName()));
	
 	$board = new ScoreBoard($exam);
	$questions = $board->getQuestions();
	
	printf("<table>\n");
	// 
	// Print questions, leave the first cell empty.
	// 
	printf("<tr><td>&nbsp;</td>");
	foreach($questions as $question) {
	    printf("<td><a href=\"?exam=%d&amp;action=correct&amp;question=%d\" title=\"%s\">%s</a></td>",
		   $question->getExamID(),
		   $question->getQuestionID(),
		   utf8_decode($question->getQuestionText()),
		   utf8_decode($question->getQuestionName()));
	}
	printf("<td>%s</td>\n", _("Summary"));
	printf("</tr>\n");
	// 
	// Output the list of anonymous students.
	// 
	$students = $board->getStudents();
	foreach($students as $student) {
	    printf("<tr><td><a href=\"?exam=%d&amp;action=correct&amp;student=%d\">%s</a></td>",
		   $student->getExamID(), 
		   $student->getStudentID(),
		   $student->getStudentCode());
	    foreach($questions as $question) {
		$data = $board->getData($student->getStudentID(), $question->getQuestionID());
		if(!isset($data)) {
		    printf("<td class=\"na\">-</td>");
		} elseif($data->getQuestionPublisher() == phpCAS::getUser()) {
		    if($data->hasResultScore()) {
			printf("<td class=\"ac\"><a href=\"?exam=%d&amp;action=correct&amp;answer=%d\">%.01f</a></td>",
			       $data->getExamID(), $data->getAnswerID(), $data->getResultScore());
		    } else {
			printf("<td class=\"nc\"><a href=\"?exam=%d&amp;action=correct&amp;answer=%d\">X</a></td>",
			       $data->getExamID(), $data->getAnswerID());
		    }
		} else {
		    if($data->hasResultScore()) {
			printf("<td class=\"no\">%.01f</td>", $data->getResultScore());
		    } else {
			printf("<td class=\"no\">?</td>");
		    }
		}
	    }
	    $score = $board->getStudentScore($student->getStudentID());
	    printf("<td>%.01f/%.01f/%.01f</td>", $score->getSum(), $score->getMax(), $board->getMaximumScore());
	    printf("</tr>\n");
	}
	
	printf("</table>\n");
	
    }
            
}

$page = new CorrectionPage();
$page->render();

?>
