<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/decoder.php
// Author: Anders Lövgren
// Date:   2010-05-05
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
include "include/teacher/manager.inc";
include "include/teacher/decoder.inc";
include "include/teacher/correct.inc";

// 
// The decoder page:
// 
class DecoderPage extends TeacherPage
{
    private $params = array( "exam" => "/^\d+$/",
			     "mode" => "/^(save)$/" );
    
    public function __construct()
    {
	parent::__construct(_("Decoder Page"), $this->params);
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
	    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
		self::saveResults($_REQUEST['exam']);
	    } else {
		self::showResults($_REQUEST['exam']);
	    }
	} else {
	    self::showAvailableExams();
	} 
    }

    // 
    // Verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess($exam)
    {
	$role = "decoder";
	
	if(!Teacher::userHasRole($exam, $role, phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
	    exit(1);
	}
	
	$manager = new Manager($exam);
	if(!$manager->getInfo()->isDecodable()) {	
	    ErrorPage::show(_("Can't continue!"),
			    _("This examination is not yet decodable, probably becuase not all answers have been corrected yet. The script processing has halted."));
	    exit(1);
	}
    }

    // 
    // This function flags the exam as decoded and shows the score board
    // with the anonymous identity disclosed.
    // 
    private function showResults($exam)
    {
	$manager = new Manager($exam);
	$decoder = new Decoder($exam);
	
	$data = $manager->getData();
	
	$decoded = $data->getExamDecoded() == 'N' ? true : false;
	$decoder->setDecoded();
	
	printf("<h3>" . _("Decoded Examination") . "</h3>\n");
	if($decoded) {
	    printf("<p>"  . _("The examination has been successful decoded. It's no longer possible change the correction of answers in this examination.") . "</p>\n");
	}
	$data = $manager->getData();  // refresh
	
	self::showScoreBoard($manager, $data);
    }
    
    private function showScoreBoard(&$manager, &$data)
    {
	printf("<h5>" . _("Answer Results") . "</h5>\n");
	printf("<p>" . 
	       _("This table shows all answers from students to questions for the examination '%s'. ") .
	       "</p>\n",
	       utf8_decode($data->getExamName()));
	
 	$board = new ScoreBoard($manager->getExamID());
	$questions = $board->getQuestions();
	
	printf("<table>\n");
	printf("<tr><td>%s</td><td>%s</td>", _("User"), _("Code"));
	$i = 1;
	foreach($questions as $question) {
	    printf("<td><a name=\"%d:%d\" title=\"%s\">Q%d.</a></td>",
		   $question->getExamID(),
		   $question->getQuestionID(),
		   sprintf("%s %s\n\n%s\n\n%s: %.01f",
			   _("Question"),
			   utf8_decode($question->getQuestionName()),
			   utf8_decode($question->getQuestionText()),
			   _("Max score"),
			   $question->getQuestionScore()),
		   $i++);
	}
	printf("<td>%s</td>\n", _("Summary"));
	printf("<td>%s</td>\n", _("Percent"));
	printf("</tr>\n");
	// 
	// Output the list of decoded students.
	// 
	$students = $board->getStudents();
	foreach($students as $student) {
	    printf("<tr><td>%s</td><td>%s</td>",
		   $student->getStudentUser(),
		   $student->getStudentCode());
	    foreach($questions as $question) {
		$data = $board->getData($student->getStudentID(), $question->getQuestionID());
		if(!isset($data)) {
		    printf("<td class=\"na\">-</td>");
		} elseif($data->getQuestionPublisher() == phpCAS::getUser()) {
		    if($data->hasResultScore()) {
			printf("<td class=\"ac\">%.01f</td>", $data->getResultScore());
		    } else {
			printf("<td class=\"nc\">X</td>");
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
	    printf("<td>%.01f%%</td>", 100 * $score->getSum() / $board->getMaximumScore());
	    printf("</tr>\n");
	}
	printf("</table>\n");

	printf("<h5>" . _("Download Result") . "</h5>\n");
	printf("<p>" . _("Click <a href=\"%s\">here</a> to download the score board.") . "</p>\n", 
	       sprintf("?exam=%d&amp;mode=save", $manager->getExamID()));
    }
    
    // 
    // Save result from exam.
    // 
    private function saveResults($exam)
    {
	$manager = new Manager($exam);	
	$data = $manager->getData();
	$info = $manager->getInfo();
	
	if(!$info->isDecoded()) {
	    ErrorPage::show(_("Can't continue!"),
			    _("This examination has not been decoded. The script processing has halted."));
	    exit(1);
	}
	
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
	printf("%s\t%s", _("User"), _("Code"));
	foreach($questions as $question) {
	    printf("\tQ%d.", $i++);
	}
	printf("\t%s", _("Score"));
	printf("\t%s", _("Possible"));
	printf("\t%s", _("Max score"));
	printf("\t%s\n", _("Percent"));
	// 
	// Output the list of anonymous students.
	// 
	$students = $board->getStudents();
	foreach($students as $student) {
	    printf("%s\t%s", $student->getStudentUser(), $student->getStudentCode());
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
	    printf("\t%.01f", $score->getSum());
	    printf("\t%.01f", $score->getMax());
	    printf("\t%.01f", $board->getMaximumScore());
	    printf("\t%.01f", 100 * $score->getSum() / $board->getMaximumScore());
	    printf("\n");
	}
	exit(0);
    }
    
    // 
    // Show all exams where caller has been granted the decoder role.
    // 
    private function showAvailableExams()
    {
	printf("<h3>" . _("Decode Examinations") . "</h3>\n");
	printf("<p>"  . _("These are the examinations that you have been granted the decoder role. Click on one of them to decode the examination.") . "</p>\n");
	printf("<p>"  . _("By decoding an examination it will no longer be possible to correct any answers for it. This is to ensure that the anonymity of each student examination.") . "</p>\n");
	
	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();
	
	$exams = Decoder::getExams(phpCAS::getUser());	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    $child = $root->addChild(utf8_decode($exam->getExamName()));
	    if($manager->getInfo()->isDecodable()) {
		$child->setLink(sprintf("?exam=%d", $exam->getExamID()),
				_("Click on this link to decode this examination."));
		$child->addLink(_("Decode"), 
				sprintf("?exam=%d", $exam->getExamID()),
				_("Click on this link to decode this examination."));
	    }
	    $child->addChild(sprintf("%s: %s", _("Decoded"), $exam->getExamDecoded() == 'Y' ? _("Yes") : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($exam->getExamStartTime()))));
	    $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($exam->getExamEndTime()))));
	}
	$tree->output();
    }
}

$page = new DecoderPage();
$page->render();

?>
