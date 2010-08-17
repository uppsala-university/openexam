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
include('Mail.php');
include('Mail/mime.php');

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
include "include/html.inc";

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/exam.inc";
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/decoder.inc";
include "include/teacher/correct.inc";

// 
// Support classes:
// 
include "include/pdf.inc";
include "include/smtp.inc";
include "include/sendmail.inc";
include "include/scoreboard.inc";

// 
// Settings for result mail attachments.
// 
if(!defined('ATTACH_MAX_FILE_SIZE')) {
    define ('ATTACH_MAX_FILE_SIZE', 1024 * 1024);
}
if(!defined('ATTACH_MAX_NUM_FILES')) {
    define ('ATTACH_MAX_NUM_FILES', 3);
}

// 
// The decoder page:
// 
class DecoderPage extends TeacherPage
{
    private $params = array( "exam"     => "/^\d+$/",
			     "mode"     => "/^(result|scores)$/",
			     "action"   => "/^(save|show|mail|download)$/", 
			     "format"   => "/^(pdf|html|ps|csv|tab|xml)$/",
			     "student"  => "/^(\d+|all)$/",
			     "colorize" => "/^\d+$/",
			     "verbose"  => "/^\d+$/" );
    
    private $decoder;
    
    public function __construct()
    {
	$this->param->verbose = false;
	$this->param->colorize = false;
	
	parent::__construct(_("Decoder Page"), $this->params);
	
	if(isset($_REQUEST['exam'])) {
	    $this->decoder = new Decoder($_REQUEST['exam']);
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
	    self::checkAccess();
	    self::setDecoded();
	}
	
	// 
	// Bussiness logic:
	// 
	if(isset($_REQUEST['exam'])) {
	    if(!isset($_REQUEST['action'])) {
		$_REQUEST['action'] = "download";
	    }
	    if($_REQUEST['action'] == "download") {
		self::showDownload();
	    } elseif($_REQUEST['action'] == "show") {
		self::showScores();
	    } elseif($_REQUEST['action'] == "save") {
		self::assert("mode");
		if($_REQUEST['mode'] == "result") {
		    self::assert(array("format", "student"));
		    self::saveResult();
		} elseif($_REQUEST['mode'] == "scores") {
		    self::assert("format");
		    self::saveScores();
		}
	    } elseif($_REQUEST['action'] == "mail") {
		if(isset($_REQUEST['student'])) {
		    self::assert("format");
		    self::sendResult();
		} else {
		    self::mailResult();
		}
	    }
	} else {
	    self::showAvailableExams();
	}
    }

    public function printMenu()
    {	
	if(isset($_REQUEST['exam'])) {
            printf("<span id=\"menuhead\">%s</span>\n", _("Decoder:"));
	    printf("<ul>\n");
	    printf("<span id=\"menuhead\">%s</span>\n", _("Result:"));
	    printf("<ul>\n");
	    printf("<li><a href=\"?exam=%d&amp;action=download\">%s</a></li>\n", $_REQUEST['exam'], _("Download"));
	    printf("<li><a href=\"?exam=%d&amp;action=mail\">%s</a></li>\n", $_REQUEST['exam'], _("Send by email"));
	    printf("</ul>\n");
	    printf("<br/>\n");
	    printf("<span id=\"menuhead\">%s</span>\n", _("Score board:"));
	    printf("<ul>\n");
	    printf("<li><a href=\"?exam=%d&amp;action=download\">%s</a></li>\n", $_REQUEST['exam'], _("Download"));
	    printf("<li><a href=\"?exam=%d&amp;action=show\">%s</a></li>\n", $_REQUEST['exam'], _("Show"));
	    printf("</ul>\n");
	    printf("<br/>\n");
	    printf("</ul>\n");
	    parent::printMenu();   // print parent menu
	}
    }

    // 
    // Verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess()
    {
	$role = "decoder";
	
	if(!Teacher::userHasRole($this->param->exam, $role, phpCAS::getUser())) {
	    ErrorPage::show(_("Access denied!"),
			    sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), $role));
	    exit(1);
	}
	
	if(!$this->manager->getInfo()->isDecodable()) {	
	    ErrorPage::show(_("Can't continue!"),
			    _("This examination is not yet decodable, probably becuase not all answers have been corrected yet. The script processing has halted."));
	    exit(1);
	}
    }
    
    // 
    // This function flags the exam as decoded. It should be called whenever
    // script execution calls an function that reveals the real identities.
    // 
    private function setDecoded()
    {
	$data = $this->manager->getData();
	
	$decoded = $data->getExamDecoded() == 'N' ? true : false;
	$this->decoder->setDecoded();
	
	printf("<h3>" . _("Decoded Examination") . "</h3>\n");
	if($decoded) {
	    printf("<p>"  . _("The examination has been successful decoded. It's no longer possible change the correction of answers in this examination.") . "</p>\n");
	}
    }
    
    // 
    // Save result from exam. The result is the complete examination for an 
    // student. If student equals all, then all student result is downloaded
    // as an zip archive.
    // 
    private function saveResult()
    {
	ob_end_clean();
	
	switch($this->param->format) {
	 case "pdf":
	 case "html":
	 case "ps":
	    if($this->param->student == "all") {    // Send zip-file containing all results.
		$result = new ResultPDF($this->param->exam);
		$result->setFormat($this->param->format);
		$result->sendAll();
	    } else {
		$result = new ResultPDF($this->param->exam);
		$result->setFormat($this->param->format);
		$result->send($this->param->student);
	    }
	    break;
	 default:
	    die(sprintf("Format %s is not supported in result mode.", $this->param->format));
	}
    }

    private function saveScores()
    {
	if(!$this->manager->getInfo()->isDecoded()) {
	    ErrorPage::show(_("Can't continue!"),
			    _("This examination has not been decoded. The script processing has halted."));
	    exit(1);
	}
	
	ob_end_clean();

	$stream = fopen("php://memory", "r+");
	if($stream) {
	    switch($this->param->format) {
	     case "pdf":
	     case "ps":
		die("TODO: implement saving score board as PDF and PostScript");
		break;
	     case "html":
		$format = new OutputTextHtml();
		break;
	     case "tab":
		$format = new OutputTextTab();
		break;
	     case "csv":
		$format = new OutputTextCsv();
		break;
	     case "xml":
		$format = new OutputTextXml();
		break;
	     default:
		die(sprintf("Format %s is not supported in score board mode.", $this->param->format));
	    }
	    
	    if(isset($format)) {
		$writer = new StreamWriter($stream, $format);
		$sender = new ScoreBoardWriter($this->param->exam, $writer, $format);
		$sender->send();
		fclose($stream);
		exit(1);
	    }
	}
    }
            
    // 
    // Show the page where caller can chose to download the result and score
    // board in different formats.
    // 
    private function showDownload()
    {
	global $locale;
	
	// 
	// The form for downloading the results:
	// 
	printf("<h5>" . _("Download Result") . "</h5>\n");	
	printf("<p>"  . 
	       _("This section lets you download the results for all or individual students in different formats. ") . 
	       _("The result contains the complete examination with answers and scores.") .
	       "</p>\n");
	printf("<p>"  .	
	       _("Notice that the language used in the generated file will be the same as your currently selected language (%s).") . 
	       "</p>\n", _($locale));
	
	$options = array( "pdf" => "Adobe PDF", "ps" => "PostScript", "html" => "HTML" );
	
	$form = new Form("decoder.php", "GET");
	$form->addHidden("exam", $this->param->exam);
	$form->addHidden("mode", "result");
	$form->addHidden("action", "save");
	
	$combo = $form->addComboBox("format");
	$combo->setLabel(_("Format"));
	foreach($options as $name => $label) {
	    $combo->addOption($name, $label);
	}
	
	$combo = $form->addComboBox("student");
	$combo->setLabel(_("Select"));
 	$board = new ScoreBoard($this->param->exam);	
	$students = $board->getStudents();
	$option = $combo->addOption("all", _("All Students"));
	$option = $combo->addOption(0, "---");
	$option->setDisabled();
	foreach($students as $student) {
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    $combo->addOption($student->getStudentID(), 
			      sprintf("%s (%s) [%s]", 
				      $student->getStudentName(),
				      $student->getStudentUser(),
				      $student->getStudentCode()));
	}
	$button = $form->addSubmitButton("submit", _("Download"));
	$button->setLabel();
	$button->setTitle(_("Please note that it might take some time to complete your request, especial if the examination has a lot of students."));
	$form->output();

	// 
	// The form for downloading the score board:
	// 
	printf("<h5>" . _("Download Score Board") . "</h5>\n");	
	printf("<p>"  . 
	       _("This section lets you download the score board showing a summary view of the examination in different formats. ") . 
	       "</p>\n");
	
	$options = array( "tab" => "Tab Separated Text", "csv" => "Comma Separated Text", "xml" => "XML Format Data", "html" => "Single HTML Page" );
	
	$form = new Form("decoder.php", "GET");
	$form->addHidden("exam", $this->param->exam);
	$form->addHidden("mode", "scores");
	$form->addHidden("action", "save");
	$combo = $form->addComboBox("format");
	$combo->setLabel(_("Format"));
	foreach($options as $name => $label) {
	    $combo->addOption($name, $label);
	}
	$button = $form->addSubmitButton("submit", _("Download"));
	$button->setLabel();
	$form->output();
    }
    
    // 
    // Shows the score board with the anonymous identity disclosed.
    // 
    private function showScores()
    {
	$data = $this->manager->getData();
	
	printf("<h5>" . _("Answer Results") . "</h5>\n");
	printf("<p>" . 
	       _("This table shows all answers from students to questions for the examination '%s'. ") .
	       "</p>\n",
	       utf8_decode($data->getExamName()));

 	printf("<span class=\"links viewmode\">");
	if($this->param->verbose) {
	    printf("%s: <a href=\"?exam=4&amp;action=show&amp;verbose=0\">%s</a>, ", _("Details"), _("Less"));
	} else {
	    printf("%s: <a href=\"?exam=4&amp;action=show&amp;verbose=1\">%s</a>, ", _("Details"), _("More"));
	}
	if($this->param->colorize) {
	    printf("%s: <a href=\"?exam=4&amp;action=show&amp;colorize=0\">%s</a>", _("Mode"), _("Standard"));
	} else {
	    printf("%s: <a href=\"?exam=4&amp;action=show&amp;colorize=1\">%s</a>", _("Mode"), _("Colorize"));
	}
	printf("</span>\n");
	
 	$board = new ScoreBoardPrinter($this->param->exam);
	$board->setVerbose($this->param->verbose);
	$board->setColorized($this->param->colorize);
	$board->output();

	printf("<h5>" . _("Color Codes") . "</h5>\n");
	printf("<p>"  . _("These are the color codes used in the score board:") . "</p>\n");
	if($this->param->colorize) {
	    $codes = array( 
			    "s0"   => sprintf(_("Less than %d%% of max score."), 20),
			    "s20"  => sprintf(_("Between %d and %d %% of max score."), 20, 40),
			    "s40"  => sprintf(_("Between %d and %d %% of max score."), 40, 60),
			    "s60"  => sprintf(_("Between %d and %d %% of max score."), 60, 80),
			    "s80"  => sprintf(_("Between %d and %d %% of max score."), 80, 99),
			    "s100" => sprintf(_("%d%% correct answer (full score)."), 100));
	} else {
	    $codes = array( 
			    "ac" => _("Answer has been corrected."),
			    "no" => _("This answer should be corrected by another person."),
			    "na" => _("No answer was given for this question."),
			    "nc" => _("The answer has not yet been corrected."),
			    "qr" => _("Question is flagged as removed (no scores for this question is counted).")
			    );	
	}
	$table = new Table();
	foreach($codes as $code => $desc) {
	    $row = $table->addRow();
	    $row->addData()->setClass(sprintf("cc %s", $code));
	    $row->addData($desc);
	}
	$table->output();
    }
    
    // 
    // Send the email message. The message is either sent to myself (debug), all students
    // or individual students.
    // 
    private function sendResult()
    {
	printf("<h5>" . _("Sending Result") . "</h5>\n");

	$from = $this->getMailRecepient(phpCAS::getUser());
	$data = $this->manager->getData();
	$mail = new MailResult($data->getExamName(), $data->getExamStartTime(), $from, $from);
	
	// 
	// Append any uploaded files.
	// 
	for($i = 0; $i < ATTACH_MAX_NUM_FILES; $i++) {
	    if($_FILES['attach']['error'][$i] == 0 && $_FILES['attach']['size'][$i] > 0) {    // successful uploaded
		if(is_uploaded_file($_FILES['attach']['tmp_name'][$i])) {
		    $mail->addAttachment($_FILES['attach']['name'][$i], 
					 $_FILES['attach']['type'][$i],
					 $_FILES['attach']['tmp_name'][$i]);
		}
	    }
	}
	
	// 
	// Append optional message.
	// 
	if(isset($_REQUEST['message'])) {
	    $lines = split("\n", $_REQUEST['message']);
	    if(count($lines) > 0) {
		$sect = array();
		foreach($lines as $line) {
		    $line = trim($line);
		    if(strlen($line) == 0) {
			$sect[] = "\n\n";
		    } elseif($line[0] == "-") {
			$curr = array_pop($sect);
			if(count($sect)) {
			    $head = array_shift($sect);
			    $text = "  " . implode(" ", $sect);
			    $mail->addMessage($head, $text);
			}
			$sect = array();
			$sect[] = $curr;
		    } else {
			$sect[] = $line;
		    }
		}
		$head = array_shift($sect);
		$text = "  " . implode(" ", $sect);
		$mail->addMessage($head, $text);
	    }
	}
	
	$result = new ResultPDF($this->param->exam);
	$result->setFormat($this->param->format);
	
	if($this->param->student == "all") {
	    $students = $this->manager->getStudents();
	} else {
	    $students = array($this->manager->getStudentData($this->param->student));
	}
	
	foreach($students as $student) {
	    $addr = $this->getMailRecepient($student->getStudentUser());
	    if(!isset($mail)) {
		$this->error(sprintf(_("Failed lookup email address for %s"), $student->getStudentUser()));
		continue;
	    }
	    $file = tempnam("/tmp", "openexam-result");
	    $result->save($student->getStudentID(), $file);
	    
	    if(strstr($this->param->format, "pdf")) {
		$attach = new MailAttachment("result.pdf", "application/pdf", $file);
	    } elseif(strstr($this->param->format, "ps")) {
		$attach = new MailAttachment("result.ps", "application/postscript", $file);
	    } elseif(strstr($this->param->format, "html")) {
		$attach = new MailAttachment("result.html", "text/html", $file);
	    }
	    
	    if(isset($_REQUEST['mirror'])) {
		$mail->setFrom($addr);
		$mail->send($from, $attach);
		$this->success(sprintf(_("Successful sent message to <a href=\"mailto:%s\">%s</a>"), $from->getEmail(), $from->getName()));
	    } else {
		$mail->setBcc($from);
		$mail->send($addr, $attach);
		$this->success(sprintf(_("Successful sent message to <a href=\"mailto:%s\">%s</a>"), $addr->getEmail(), $addr->getName()));
	    }
	}
	
    }
    
    // 
    // Show form for sending examination result to students.
    // 
    private function mailResult()
    {
	global $locale;
	
	// 
	// The form for sending the results by email:
	// 
	printf("<p>"  . 
	       _("This section lets you send the results to all or individual students in different formats. ") . 
	       _("The result contains the complete examination with answers and scores.") .
	       "</p>\n");
	printf("<p>"  .	
	       _("Notice that the language used in the outgoing message will be the same as your currently selected language (%s).") . 
	       "</p>\n", _($locale));

	// 
	// The format and student select section:
	// 
	$options = array( "pdf" => "Adobe PDF", "ps" => "PostScript", "html" => "HTML" );
	$form = new Form("decoder.php", "POST");
	$form->setEncodingType("multipart/form-data");
	$form->addHidden("MAX_FILE_SIZE", ATTACH_MAX_FILE_SIZE);
	$form->addHidden("exam", $this->param->exam);
	$form->addHidden("mode", "result");
	$form->addHidden("action", "mail");
		
	$form->addSectionHeader(_("Send Result"));
	$combo = $form->addComboBox("format");
	$combo->setLabel(_("Format"));
	foreach($options as $name => $label) {
	    $combo->addOption($name, $label);
	}
	$combo = $form->addComboBox("student");
	$combo->setLabel(_("Select"));
	$board = new ScoreBoard($this->param->exam);	
	$students = $board->getStudents();
	$option = $combo->addOption("all", _("All Students"));
	$option = $combo->addOption(0, "---");
	$option->setDisabled();
	foreach($students as $student) {
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    $combo->addOption($student->getStudentID(),
			      sprintf("%s (%s) [%s]", 
				      $student->getStudentName(),
				      $student->getStudentUser(),
				      $student->getStudentCode()));
	}
	
	// 
	// The optional message section:
	// 
	$form->addSectionHeader(_("Optional Message"));
	$input = $form->addTextArea("message", _("Header 1:\n---\nSome text for this first section...\n\nHeader 2:\n---\nIt's possible to have multiple blocks of text separated by newlines:\n\nFirst block...\n\n...and second block.\n"));
	$input->setLabel(_("Text"));
	$input->setClass("message");
	$input->setTitle(_("Append one or more optional section of text to the message."));
	$input->setEvent(EVENT_ON_CLICK, EVENT_HANDLER_CLEAR_CONTENT);
	
	// 
	// The attachment section:
	// 
	$form->addSectionHeader(_("Attachements"));
	for($i = 0; $i < ATTACH_MAX_NUM_FILES; $i++) {
	    $input = $form->addFileInput("attach[]");
	    $input->setLabel();
	    $input->setClass("file");
	    $input->setTitle(_("Attach this file to all outgoing messages."));
	}
	$form->addSpace();
	$input = $form->addCheckBox("mirror", _("Enable mirror mode (dry-run)."));
	$input->setTitle(_("If checked, then your email address will be used as the receiver, with the student address set as the sender."));
	$input->setLabel();
	$form->addSpace();
	$button = $form->addSubmitButton("submit", _("Send"));
	$button->setLabel();
	$button->setTitle(_("Please note that it might take some time to complete your request, especial if the examination has a lot of students."));
	
	$form->output();
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
	
	// 
	// Group the examinations by their state:
	// 
	$exams = Decoder::getExams(phpCAS::getUser());
	$nodes = array( 
			'u' => array( 'name' => _("Decodable"),
				      'data' => array() ),
			'd' => array( 'name' => _("Decoded"),
				      'data' => array() ),
			'o' => array( 'name' => _("Other"),
				      'data' => array() )
			);
	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    $state = $manager->getInfo();
	    if($state->isDecoded()) {
		$nodes['d']['data'][$exam->getExamName()] = $state;
	    } elseif($state->isDecodable()) {
		$nodes['u']['data'][$exam->getExamName()] = $state;
	    } else {
		$nodes['o']['data'][$exam->getExamName()] = $state;
	    }
	}
	
	foreach($nodes as $type => $group) {
	    if(count($group['data']) > 0) {
		$node = $root->addChild($group['name']);
		foreach($group['data'] as $name => $state) {
		    $child = $node->addChild(utf8_decode($name));
		    if($state->isDecodable()) {
			$child->setLink(sprintf("?exam=%d", $state->getInfo()->getExamID()),
					_("Click on this link to decode this examination."));
			$child->addLink(_("Decode"), 
					sprintf("?exam=%d", $state->getInfo()->getExamID()),
					_("Click on this link to decode this examination."));
		    }
		    $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamStartTime()))));
		    $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamEndTime()))));
		}
	    }
	}
	$tree->output();
    }
}

$page = new DecoderPage();
$page->render();

?>
