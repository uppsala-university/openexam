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

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/exam.inc";
include "include/pdf.inc";
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/decoder.inc";
include "include/teacher/correct.inc";
include "include/smtp.inc";
include "include/sendmail.inc";

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
// Output formatters:
// 
class OutputFormatter
{
    protected $values;
    
    public function format(&$data)
    {
	$this->values = array();
	foreach($data as $value) {
	    if(is_float($value)) {
		$this->values[] = sprintf("%.01f", $value);
	    } else {
		$this->values[] = $value;
	    }
	}
    }
}

class OutputTextTab extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
        return implode("\t", $this->values);
    }
}

class OutputTextCsv extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
	return "\"" . implode("\",\"", $this->values) . "\"";
    }
}

class OutputTextXml extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
        return "<row><data>" . implode("</data><data>", $this->values) . "</data></row>";
    }
}

class OutputTextHtml extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
	return "<tr><td>" . implode("</td><td>", $this->values) . "</td></tr>";
    }
}

// 
// Writes data to the stream using the formatter object.
// 
class StreamWriter
{
    private $stream;
    private $format;

    public function __construct($stream, $format)
    {
	$this->stream = $stream;
	$this->format = $format;
    }
    
    public function getStream()
    {
	return $this->stream;
    }

    public function writeLine(&$data)
    {
	fprintf($this->stream, "%s\n", $this->format->getLine($data));
    }
}

// 
// The decoder page:
// 
class DecoderPage extends TeacherPage
{
    private $params = array( "exam"    => "/^\d+$/",
			     "mode"    => "/^(result|scores)$/",
			     "action"  => "/^(save|show|mail|download)$/", 
			     "format"  => "/^(pdf|html|ps|csv|tab|xml)$/",
			     "student" => "/^(\d+|all)$/" );

    private $manager;
    private $decoder;
    
    public function __construct()
    {
	parent::__construct(_("Decoder Page"), $this->params);
	
	if(isset($_REQUEST['exam'])) {
	    $this->manager = new Manager($_REQUEST['exam']);
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
	    self::checkAccess($_REQUEST['exam']);
	    self::setDecoded($_REQUEST['exam']);
	}
	
	// 
	// Bussiness logic:
	// 
	if(isset($_REQUEST['exam'])) {
	    if(!isset($_REQUEST['action'])) {
		$_REQUEST['action'] = "download";
	    }
	    if($_REQUEST['action'] == "download") {
		self::showDownload($_REQUEST['exam']);
	    } elseif($_REQUEST['action'] == "show") {
		self::showScores($_REQUEST['exam']);
	    } elseif($_REQUEST['action'] == "save") {
		self::assert("mode");
		if($_REQUEST['mode'] == "result") {
		    self::assert(array("format", "student"));
		    self::saveResult($_REQUEST['exam'], $_REQUEST['format'], $_REQUEST['student']);
		} elseif($_REQUEST['mode'] == "scores") {
		    self::assert("format");
		    self::saveScores($_REQUEST['exam'], $_REQUEST['format']);
		}
	    } elseif($_REQUEST['action'] == "mail") {
		if(isset($_REQUEST['student'])) {
		    self::assert("format");
		    self::sendResult($_REQUEST['exam'], $_REQUEST['student'], $_REQUEST['format']);
		} else {
		    self::mailResult($_REQUEST['exam']);
		}
	    }
	} else {
	    self::showAvailableExams();
	}
    }

    public function printMenu()
    {	
	if(isset($_REQUEST['exam'])) {
	    printf("<span id=\"menuhead\">%s</span>\n", _("Result"));
	    printf("<ul>\n");
	    printf("<li><a href=\"?exam=%d&amp;action=download\">%s</a></li>\n", $_REQUEST['exam'], _("Download"));
	    printf("<li><a href=\"?exam=%d&amp;action=mail\">%s</a></li>\n", $_REQUEST['exam'], _("Email Results"));
	    printf("</ul>\n");
	    printf("<span id=\"menuhead\">%s</span>\n", _("Score Board"));
	    printf("<ul>\n");
	    printf("<li><a href=\"?exam=%d&amp;action=download\">%s</a></li>\n", $_REQUEST['exam'], _("Download"));
	    printf("<li><a href=\"?exam=%d&amp;action=show\">%s</a></li>\n", $_REQUEST['exam'], _("Show"));
	    printf("</ul>\n");
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
    // This function flags the exam as decoded. It should be called whenever
    // script execution calls an function that reveals the real identities.
    // 
    private function setDecoded($exam)
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
    // Save result from exam. The result is the complete examination for an student.
    // If student 
    // 
    private function saveResult($exam, $format, $student)
    {
	ob_end_clean();
	
	switch($format) {
	 case "pdf":
	 case "html":
	 case "ps":
	    if($student == "all") {    // Send zip-file containing all results.
		$result = new ResultPDF($exam);
		$result->setFormat($format);
		$result->sendAll();
	    } else {
		$result = new ResultPDF($exam);
		$result->setFormat($format);
		$result->send($student);
	    }
	    break;
	 default:
	    die(sprintf("Format %s is not supported in result mode.", $format));
	}
    }
    
    private function saveScores($exam, $format)
    {
	$data = $this->manager->getData();
	$info = $this->manager->getInfo();
	
	if(!$info->isDecoded()) {
	    ErrorPage::show(_("Can't continue!"),
			    _("This examination has not been decoded. The script processing has halted."));
	    exit(1);
	}
	
	ob_end_clean();
	
	switch($format) {
	 case "pdf":
	 case "html":
	 case "ps":
	    die("TODO: implement saving score board as pdf, html and ps");
	    break;
	 case "tab":
	    self::saveScoresTab($exam, $data);
	    break;
	 case "csv":
	    self::saveScoresCsv($exam, $data);
	    break;
	 case "xml":
	    self::saveScoresXml($exam, $data);
	    break;
	 default:
	    die(sprintf("Format %s is not supported in score board mode.", $format));
	}
    }
    
    // 
    // Save the score board as tab-separated values (for import in a 
    // spread sheet application).
    // 
    private function saveScoresTab($exam, &$data)
    {
	if(ob_get_length() > 0) {
	    ob_end_clean();
	}

	$stream = fopen("php://memory", "r+");
	$format = new OutputTextTab();
	$writer = new StreamWriter($stream, $format);
	
	$this->writeScores($data, $writer, $exam);
	
    	header("Content-Type: text/tab-separated-values");
    	header(sprintf("Content-Disposition: attachment;filename=\"%s.tab\"", $data->getExamName()));
    	header("Cache-Control: no-cache");
    	header("Pragma-directive: no-cache");
    	header("Cache-directive: no-cache");
    	header("Pragma: no-cache");
    	header("Expires: 0");
			
	rewind($stream);
	echo stream_get_contents($stream);
	exit(0);
    }

    // 
    // Save the score board as comma-separated values (for import in a 
    // spread sheet application).
    // 
    private function saveScoresCsv($exam, &$data)
    {
	if(ob_get_length() > 0) {
	    ob_end_clean();
	}
	
	$stream = fopen("php://memory", "r+");	
	$format = new OutputTextCsv();
	$writer = new StreamWriter($stream, $format);
	
	$this->writeScores($data, $writer, $exam);
	
    	header("Content-Type: text/csv");
    	header(sprintf("Content-Disposition: attachment;filename=\"%s.csv\"", $data->getExamName()));
    	header("Cache-Control: no-cache");
    	header("Pragma-directive: no-cache");
    	header("Cache-directive: no-cache");
    	header("Pragma: no-cache");
    	header("Expires: 0");

	rewind($stream);
	echo stream_get_contents($stream);
	exit(0);
    }

    // 
    // Save the score board as XML formatted values (for import in a 
    // spread sheet application).
    // 
    private function saveScoresXml($exam, &$data)
    {
	if(ob_get_length() > 0) {
	    ob_end_clean();
	}

	$stream = fopen("php://memory", "r+");
	$format = new OutputTextXml();
	$writer = new StreamWriter($stream, $format);
	
	fprintf($stream, "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n");
	fprintf($stream, "<rows>\n");
	$this->writeScores($data, $writer, $exam);
	fprintf($stream, "</rows>\n");
	
    	header("Content-Type: application/xml");
    	header(sprintf("Content-Disposition: attachment;filename=\"%s.xml\"", $data->getExamName()));
    	header("Cache-Control: no-cache");
    	header("Pragma-directive: no-cache");
    	header("Cache-directive: no-cache");
    	header("Pragma: no-cache");
    	header("Expires: 0");
	
	rewind($stream);
	echo stream_get_contents($stream);
	exit(0);
    }
    
    // 
    // Format the score table using the supplied formatter object.
    // 
    private function writeScores($data, $writer, $exam)
    {
	$board = new ScoreBoard($exam);
	$questions = $board->getQuestions();

	// 
	// Write header list:
	// 
	$i = 1;	
	$array = array();
	$array[] = _("Name");
	$array[] = _("User");
	$array[] = _("Code");
	foreach($questions as $question) {
	    $array[] = sprintf("Q%d", $i++);
	}
	$array[] = _("Score");
	$array[] = _("Possible");
	$array[] = _("Max score");
	$array[] = _("Percent");
	$writer->writeLine($array);
	
	// 
	// Output the list of students.
	// 
	$students = $board->getStudents();
	foreach($students as $student) {
	    $array = array();
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    $array[] = $student->getStudentName();
	    $array[] = $student->getStudentUser();
	    $array[] = $student->getStudentCode();
	    foreach($questions as $question) {
		$data = $board->getData($student->getStudentID(), $question->getQuestionID());
		if(!isset($data)) {
		    $array[] = "";
		} else {
		    if($data->hasResultScore()) {
			$array[] = $data->getResultScore();
		    } else {
			$array[] = "";
		    }
		}
	    }
	    $score = $board->getStudentScore($student->getStudentID());
	    $array[] = $score->getSum();
	    $array[] = $score->getMax();
	    $array[] = $board->getMaximumScore();
	    $array[] = 100 * $score->getSum() / $board->getMaximumScore();
	    $writer->writeLine($array);
	}
    }
    
    // 
    // Show the page where caller can chose to download the result and score
    // board in different formats.
    // 
    private function showDownload($exam)
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
	printf("<form action=\"decoder.php\" method=\"GET\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $this->manager->getExamID());
	printf("<input type=\"hidden\" name=\"mode\" value=\"result\" />\n");
	printf("<input type=\"hidden\" name=\"action\" value=\"save\" />\n");	
	printf("<label for=\"format\">%s:</label>\n", _("Format"));
	printf("<select name=\"format\">\n");
	foreach($options as $name => $label) {
	    printf("<option value=\"%s\">%s</option>\n", $name, $label);
	}
	printf("</select>\n");
	printf("<br/>\n");
	printf("<label for=\"select\">%s:</label>\n", _("Select"));
	printf("<select name=\"student\">\n");
 	$board = new ScoreBoard($this->manager->getExamID());	
	$students = $board->getStudents();
	printf("<option value=\"all\">%s</option>\n", _("All students"));
	printf("<option value=\"0\" disabled=\"true\">---</option>\n");
	foreach($students as $student) {
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    printf("<option value=\"%d\">%s (%s) [%s]</option>\n", 
		   $student->getStudentID(),
		   $student->getStudentName(),
		   $student->getStudentUser(),
		   $student->getStudentCode());
	}
	printf("</select>\n");
	printf("<br/>\n");
	printf("<label for=\"submit\">&nbsp</label>\n");	
	printf("<input type=\"submit\" value=\"%s\" title=\"%s\" />\n", 
	       _("Download"), _("Please note that it might take some time to complete your request, especial if the examination has a lot of students."));
	printf("</form>\n");

	// 
	// The form for downloading the score board:
	// 
	printf("<h5>" . _("Download Score Board") . "</h5>\n");	
	printf("<p>"  . 
	       _("This section lets you download the score board showing a summary view of the examination in different formats. ") . 
	       "</p>\n");
	$options = array( "tab" => "Tab Separated Text", "csv" => "Comma Separated Text", "xml" => "XML Format Data" );
	printf("<form action=\"decoder.php\" method=\"GET\">\n");
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\">\n", $this->manager->getExamID());
	printf("<input type=\"hidden\" name=\"mode\" value=\"scores\" />\n");
	printf("<input type=\"hidden\" name=\"action\" value=\"save\" />\n");	
	printf("<label for=\"format\">%s:</label>\n", _("Format"));
	printf("<select name=\"format\">\n");
	foreach($options as $name => $label) {
	    printf("<option value=\"%s\">%s</option>\n", $name, $label);
	}
	printf("</select>\n");
	printf("<br/>\n");
	printf("<label for=\"submit\">&nbsp</label>\n");	
	printf("<input type=\"submit\" value=\"%s\" />\n", _("Download"));
	printf("</form>\n");
    }
    
    // 
    // Shows the score board with the anonymous identity disclosed.
    // 
    private function showScores($exam)
    {
	$data = $this->manager->getData();
	
	printf("<h5>" . _("Answer Results") . "</h5>\n");
	printf("<p>" . 
	       _("This table shows all answers from students to questions for the examination '%s'. ") .
	       "</p>\n",
	       utf8_decode($data->getExamName()));
	
 	$board = new ScoreBoard($this->manager->getExamID());
	$questions = $board->getQuestions();
	
	printf("<table>\n");
	printf("<tr><td>%s</td><td>%s</td><td>%s</td>", _("Name"), _("User"), _("Code"));
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
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    printf("<tr><td nowrap>%s</td><td>%s</td><td>%s</td>",
		   $student->getStudentName(),
		   $student->getStudentUser(),
		   $student->getStudentCode());
	    foreach($questions as $question) {
		$data = $board->getData($student->getStudentID(), $question->getQuestionID());
		if(!isset($data)) {
		    printf("<td class=\"cc na\">-</td>");
		} elseif($data->getQuestionPublisher() == phpCAS::getUser()) {
		    if($data->hasResultScore()) {
			printf("<td class=\"cc ac\">%.01f</td>", $data->getResultScore());
		    } else {
			printf("<td class=\"cc nc\">X</td>");
		    }
		} else {
		    if($data->hasResultScore()) {
			printf("<td class=\"cc no\">%.01f</td>", $data->getResultScore());
		    } else {
			printf("<td class=\"cc no\">?</td>");
		    }
		}
	    }
	    $score = $board->getStudentScore($student->getStudentID());
	    printf("<td>%.01f/%.01f/%.01f</td>", $score->getSum(), $score->getMax(), $board->getMaximumScore());
	    printf("<td>%.01f%%</td>", 100 * $score->getSum() / $board->getMaximumScore());
	    printf("</tr>\n");
	}
	printf("</table>\n");

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
    
    // 
    // Send the email message. The message is either sent to myself (debug), all students
    // or individual students.
    // 
    private function sendResult($exam, $student, $format)
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

	$result = new ResultPDF($exam);
	$result->setFormat($format);
	
	if($student == "all") {
	    $students = $this->manager->getStudents();
	} else {
	    $students = array($this->manager->getStudentData($student));
	}
	
	foreach($students as $student) {
	    $addr = $this->getMailRecepient($student->getStudentUser());
	    $file = tempnam("/tmp", "openexam-result");
	    $result->save($student->getStudentID(), $file);
	    
	    if(strstr($format, "pdf")) {
		$attach = new MailAttachment("result.pdf", "application/pdf", $file);
	    } elseif(strstr($format, "ps")) {
		$attach = new MailAttachment("result.ps", "application/postscript", $file);
	    } elseif(strstr($format, "html")) {
		$attach = new MailAttachment("result.html", "text/html", $file);
	    }
	    
	    if(isset($_REQUEST['mirror'])) {
		$mail->setFrom($addr);
		$mail->send($from, $attach);
	    } else {
		$mail->setBcc($from);
		$mail->send($addr, $attach);
	    }
	}
    }
    
    // 
    // Show form for sending examination result to students.
    // 
    private function mailResult($exam)
    {
	global $locale;
	
	// 
	// The form for sending the results by email:
	// 
	printf("<h5>" . _("Send Result") . "</h5>\n");	
	printf("<p>"  . 
	       _("This section lets you send the results to all or individual students in different formats. ") . 
	       _("The result contains the complete examination with answers and scores.") .
	       "</p>\n");
	printf("<p>"  .	
	       _("Notice that the language used in the outgoing message will be the same as your currently selected language (%s).") . 
	       "</p>\n", _($locale));
	
	$options = array( "pdf" => "Adobe PDF", "ps" => "PostScript", "html" => "HTML" );
	printf("<form enctype=\"multipart/form-data\" action=\"decoder.php\" method=\"POST\">\n");
	printf("<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"%d\" />\n", ATTACH_MAX_FILE_SIZE);
	printf("<input type=\"hidden\" name=\"exam\" value=\"%d\" />\n", $this->manager->getExamID());
	printf("<input type=\"hidden\" name=\"mode\" value=\"result\" />\n");
	printf("<input type=\"hidden\" name=\"action\" value=\"mail\" />\n");	
	printf("<label for=\"format\">%s:</label>\n", _("Format"));
	printf("<select name=\"format\">\n");
	foreach($options as $name => $label) {
	    printf("<option value=\"%s\">%s</option>\n", $name, $label);
	}
	printf("</select>\n");
	printf("<br/>\n");
	printf("<label for=\"select\">%s:</label>\n", _("Select"));
	printf("<select name=\"student\">\n");
	$board = new ScoreBoard($this->manager->getExamID());	
	$students = $board->getStudents();
	printf("<option value=\"all\">%s</option>\n", _("All Students"));
	printf("<option value=\"0\" disabled=\"true\">---</option>\n");
	foreach($students as $student) {
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    printf("<option value=\"%d\">%s (%s) [%s]</option>\n", 
		   $student->getStudentID(),
		   $student->getStudentName(),
		   $student->getStudentUser(),
		   $student->getStudentCode());
	}
	printf("</select>\n");
	printf("<h5>%s</h5>\n", _("Attachements"));
	for($i = 0; $i < ATTACH_MAX_NUM_FILES; $i++) {
	    printf("<label for=\"attach[]\">&nbsp;</label>\n");
	    printf("<input type=\"file\" class=\"file\" title=\"%s\" name=\"attach[]\" />\n", 
		   _("Attach this file to all outgoing messages."));
	    printf("<br/>\n");
	}
	printf("<br/>\n");
	printf("<label for=\"mirror\">&nbsp;</label>\n");
	printf("<input type=\"checkbox\" name=\"mirror\" title=\"%s\" />%s\n", 
	       _("If checked, then your email address will be used as the receiver, with the student address set as the sender."),
	       _("Enable mirror mode (dry-run)."));
	printf("<br/>\n");
	printf("<br/>\n");
	printf("<label for=\"submit\">&nbsp</label>\n");
	printf("<input type=\"submit\" value=\"%s\" title=\"%s\" />\n", 
	       _("Send"), _("Please note that it might take some time to complete your request, especial if the examination has a lot of students."));
	printf("</form>\n");
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
