<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/pdf.inc
// Author: Anders Lövgren
// Date:   2010-05-12
// 
// Support for generating PDF files.
// 
// Currently we do the conversion by using the application HTMLDOC for converting 
// structured HTML (called *.book files) to PDF, by piping HTML on its stdin using
// a UNIX command pipeline.
// 
// These classes should probably be modified to use a cache of already generated
// files, especial useful when generating results for a whole exam (takes about 
// 5 sec for 30 students). For a large axamination, we might hit the script execution
// timeout.
// 

if(!defined('PDF_ENGINE')) {
    define ('PDF_ENGINE', 'htmldoc');
}
if(!defined('PDF_LOGOTYPE')) {
    define ('PDF_LOGOTYPE',   sprintf('%s/images/uu-logotype-4f-84.png', BASE_URL));
}
if(!defined('PDF_LINK_COLOR')) {
    define ('PDF_LINK_COLOR', '#000099');
}
if(!defined('PDF_LINK_STYLE')) {
    define ('PDF_LINK_STYLE', 'plain');
}
if(!defined('PDF_MEDIA_SIZE')) {
    define ('PDF_MEDIA_SIZE', 'a4');
}
if(!defined('PDF_MEDIA_TYPE')) {
    define ('PDF_MEDIA_TYPE', 'plain');
}
if(!defined('PDF_MEDIA_LANDSCAPE')) {
    define ('PDF_MEDIA_LANDSCAPE', false);
}
if(!defined('PDF_FRONT_PAGE_MARGIN')) {
    define ('PDF_FRONT_PAGE_MARGIN', '60mm');
}
if(!defined('PDF_GENERATOR')) {
    define ('PDF_GENERATOR', 'OpenExam PHP');
}
if(!defined('PDF_OUTPUT_FORMAT')) {
    define ('PDF_OUTPUT_FORMAT', 'pdf14');
}
if(!defined('PDF_QUESTION_MIN_LENGTH')) {
    define ('PDF_QUESTION_MIN_LENGTH', 12);
}

// 
// All classes feeding input to HTMLDOC should implement this interface
// and call begin() passing itself as a class reference.
// 
interface InputGenerator
{
    public function write($stream);
}

class HtmlDocPDF
{
    protected $title;     // The page title
    protected $author;    // The publisher
    
    protected $debug = false;
    protected $format = PDF_OUTPUT_FORMAT;
    protected $frontpage = null;
    
    protected function __construct($title, $author)
    {
	$this->title  = $title;
	$this->author = $author;
    }

    // 
    // Remember to remove the temporary front page file.
    // 
    public function __destruct()
    {
	if(file_exists($this->frontpage)) {
	    unlink($this->frontpage);
	}
    }
    
    // 
    // Enable debug (no command is executed and command input is written
    // to stdout instead).
    // 
    public function setDebug($value)
    {
	$this->debug = $value;
    }
    
    // 
    // Specifies the output format. Possible values are: 
    // 
    // *) "html":    Output plain HTML.
    // *) "htmlsep": Separate HTML files for each heading in the table-of-contents.
    // *) "ps" or
    //    "ps2":     PostScript Level 2.
    // *) "ps1":     PostScript Level 1.
    // *) "ps3":     PostScript Level 3.
    // *) "pdf11":   PDF 1.1/Acrobat 2.0.
    // *) "pdf12":   PDF 1.2/Acrobat 3.0.
    // *) "pdf" or
    //    "pdf13":   PDF 1.3/Acrobat 4.0.
    // *) "pdf14":   PDF 1.4/Acrobat 5.0.
    // 
    public function setFormat($value)
    {
	$this->format = $value;
    }
    
    // 
    // Get the command string for writing the PDF to target. If target is null,
    // then output is written to stdout.
    // 
    private function getCommand($target)
    {	
	$fmt = "htmldoc - --book --format %s --titlefile %s --linkstyle %s --size %s --linkcolor '%s' --toctitle '%s' --toclevels 2";
	$cmd = sprintf($fmt, 
		       $this->format, 
		       $this->frontpage,
		       PDF_LINK_STYLE, 
		       PDF_MEDIA_SIZE, 
		       PDF_LINK_COLOR, 
		       _("Table of Contents"));
	if(isset($target)) {
	    $cmd .= sprintf(" -f %s", $target);
	}
	return $cmd;
    }
    
    // 
    // Open the HTMLDOC pipeline and call back using the class reference to 
    // get the input data using the multiplexed stream resource.
    // 
    protected function begin($class, $target = null) 
    {	
	if($this->debug) {
	    $pipes = array( fopen("php://stdout", "w") );
	} else {
	    $fds = array( 
			  0 => array("pipe", "r"),
			  1 => array("pipe", "w"),
			  2 => array("pipe", "w")
			  );
	    
	    $cmd = $this->getCommand($target);
	    $env = array( "SHELL" => "/bin/bash", "TZ" => "UTC" );
	    $pipes = array();
	    
	    if(version_compare(phpversion(), "5.0.0") < 0) {
		$timezone = getenv("TZ");
		putenv(sprintf("TZ=%s", $env['TZ']));
		$process = proc_open($cmd, $fds, $pipes);
		putenv(sprintf("TZ=%s", $timezone));
	    }
	    else {
		$process = proc_open($cmd, $fds, $pipes, null, $env);
	    }
	}
	
	if(is_resource($process) || $this->debug) {
	    $class->write($pipes[0]);
	    
	    if(!$this->debug) {
		fclose($pipes[0]);
		
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		
		if(strlen($stderr) > 0) {
		    // 
		    // HTMLDOC got the bad habit of writing status info to stderr.
		    // 
		    if(!preg_match("/^(BYTES: \d+|PAGES: \d+)$/msU", $stderr)) {
			throw new Exception(sprintf("Failed generate PDF: %s", $stderr));
		    }
		}
		if(strlen($stdout) > 0) {		    
		    echo $stdout;
		}
		
		fclose($pipes[1]);
		fclose($pipes[2]);
		
		$result = proc_close($process);
		
		//
		// Check exit status of process.
		//
		if($result != 0) {
		    throw new Exception(sprintf("HTMLDOC finished with non-zero exit code %d", $result), $result);
		}
	    }
	}
    }
    
}

// 
// Generates the examination result PDF for a single student or all students
// registered on the exam. The output can either be sent to the browser or
// saved to a file.
// 
class ResultPDF extends HtmlDocPDF implements InputGenerator
{
    private $manager;
    private $student;   // Current student ID
    private $exam;
    private $ldap;
    
    public function __construct($examid)
    {
	$this->manager = new Manager($examid);
	$this->exam = $this->manager->getData();
	$this->ldap = LDAPSearch::factory();
	
	parent::__construct($this->exam->getExamName(), 
			    $this->exam->getExamOrgUnit());
    }
    
    public function __destruct()
    {
	$this->ldap->close();
    }

    // 
    // Create the frontpage. If previous frontpage exists, then it's
    // unlinked (deleted) before the new one is created.
    // 
    private function createFrontPage($name)
    {		
	$stream = fopen($this->frontpage, "w");
	if($stream) {
	    fprintf($stream, "<html>\n");
	    fprintf($stream, "<body>\n");
	    fprintf($stream, "<!-- MEDIA LEFT %s  -->\n", PDF_FRONT_PAGE_MARGIN);
	    fprintf($stream, "<!-- MEDIA RIGHT %s -->\n", PDF_FRONT_PAGE_MARGIN);
	    fprintf($stream, "<br/><br/>\n");
	    fprintf($stream, "<center>\n");
	    fprintf($stream, "<img src=\"%s\" />\n", PDF_LOGOTYPE);
	    fprintf($stream, "<br/><br/><br/><br/><br/><br/>\n");
	    fprintf($stream, "<h1>%s</h1>\n", $this->title);
	    fprintf($stream, "<h2>%s</h2>\n", $this->author);
	    fprintf($stream, "<br/><br/><br/><br/>\n");
	    fprintf($stream, "<table><tr><td align=\"left\">\n");
	    fprintf($stream, "<h4>%s: %s</h4>\n", _("Examination Date"), strftime(DATE_FORMAT, strtotime($this->exam->getExamStartTime())));
	    fprintf($stream, "<h4>%s: %s</h4>\n", _("Participant"), $name);
	    fprintf($stream, "</td></tr></table>\n");
	    fprintf($stream, "</center>\n");
	    fprintf($stream, "</body>\n");
	    fprintf($stream, "</html>\n");
	    
	    fclose($stream);
	}
    }
    
    // 
    // Output meta data.
    // 
    private function printMetaData($stream)
    {
	fprintf($stream, "<!-- FOOTER CENTER \"\$CHAPTER\" -->\n");
	fprintf($stream, "<!-- FOOTER LEFT \"%s\" -->\n", _('Page $PAGE of $PAGES'));
	fprintf($stream, "<!-- FOOTER RIGHT \"\$DATE \$TIME\" -->\n");
	
	fprintf($stream, "<!-- MEDIA SIZE %s -->\n", strtoupper(PDF_MEDIA_SIZE));
	fprintf($stream, "<!-- MEDIA TYPE %s -->\n", strtoupper(PDF_MEDIA_TYPE));
	fprintf($stream, "<!-- MEDIA LANDSCAPE %s -->\n", PDF_MEDIA_LANDSCAPE ? "YES" : "NO");
	
	fprintf($stream, "<META NAME=\"AUTHOR\" CONTENT=\"%s\" />\n", $this->author);
	fprintf($stream, "<META NAME=\"GENERATOR\" CONTENT=\"%s\" />\n", PDF_GENERATOR);
	fprintf($stream, "<META NAME=\"KEYWORDS\" CONTENT=\"exam,openexam,php,online\" />\n");
	fprintf($stream, "<META NAME=\"SUBJECT\" CONTENT=\"%s\" />\n", $this->title);
    }
    
    // 
    // Output the examination description section.
    // 
    private function printSectionDescription($stream) 
    {
	fprintf($stream, "<h1>%s</h1>\n", _("Description"));
	fprintf($stream, "<p>%s</p>\n", 
		str_replace("\n", "<br/>", utf8_decode($this->exam->getExamDescription())));
    }

    // 
    // Output the scores section.
    // 
    private function printSectionScores($stream)
    {
	$board = new ScoreBoard($this->exam->getExamID());
	
	fprintf($stream, "<h1>%s</h1>\n", _("Examination Result"));
	
	// 
	// The summary section.
	// 
	$score = $board->getStudentScore($this->student);
	fprintf($stream, "<h2>%s</h2>\n", _("Summary"));
	fprintf($stream, "<p>" . _("You scored total %.01f of maximum %.01f points (%.01f%%) on this examination.") . "</p>\n",
		$score->getSum(), 
		$board->getMaximumScore(), 
		100 * $score->getSum() / $board->getMaximumScore());
	
	// 
	// The answer result details section.
	// 
	fprintf($stream, "<h2>%s</h2>\n", _("Score Table"));
	fprintf($stream, "<p>%s</p>\n",  _("This table shows a summary of your score versus the maximum scores. Questions without answer are marked with an '-'."));
	fprintf($stream, "<table><tr><th align=\"right\">%s</th><th>&nbsp;</th><th>%s</th><th>%s</th><th>%s</th></tr>\n", 
		_("Question"), _("Score"), _("Max score"), _("Percent"));
	$questions = $board->getQuestions();
	foreach($questions as $question) {
	    fprintf($stream, "<tr><td align=\"right\">%s</td><td>&nbsp;</td>", utf8_decode($question->getQuestionName()));
	    $data = $board->getData($this->student, $question->getQuestionID());
	    if(isset($data)) {
 		fprintf($stream, "<td>%.01f</td><td>%.01f</td><td>%.01f<td></tr>\n",
			$data->getResultScore(),
			$question->getQuestionScore(),
			100 * $data->getResultScore() / $question->getQuestionScore());
	    } else {
		fprintf($stream, "<td>-</td><td>%.01f</td><td>%.01f<td></tr>\n",
			$question->getQuestionScore(), 0);		
	    }
	}
	fprintf($stream, "<tr><td align=\"right\">---</td><td>&nbsp;</td><td>---</td><td>---</td><td>---</td></tr>\n");
	fprintf($stream, "<tr><td><b>%s:</b></td><td>&nbsp;</td><td><b>%.01f</b></td><td><b>%.01f</b></td><td><b>%.01f%%</b></td></tr>\n",
		_("Summary"), 
		$score->getSum(), 
		$board->getMaximumScore(), 
		100 * $score->getSum() / $board->getMaximumScore());
	fprintf($stream, "</table>\n");
    }
    
    // 
    // Helper function for printing a question and (possibly) the students
    // answer to it.
    // 
    private function printQuestionAnswer($stream, $question, $answer)
    {
	fprintf($stream, "<!-- NEED %d -->\n<br/>\n", PDF_QUESTION_MIN_LENGTH);
	fprintf($stream, "<h2>%s: %s</h2>\n", _("Question"), utf8_decode($question->getQuestionName()));
	    
	// 
	// Output tags for image if question has one:
	// 
	if($question->hasQuestionImage()) {
	    fprintf($stream, "<img src=\"%s\" />\n", $question->getQuestionImage());
	}
	
	// 
	// Print the block of text containing the question. Format this 
	// section different depending on the question type.
	// 
	if($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
	    fprintf($stream, "<p>%s</p>\n", str_replace("\n", "<br/>", utf8_decode($question->getQuestionText())));
	} elseif($question->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE ||
		 $question->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
	    $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
	    fprintf($stream, "<p><b>%s</b></p>\n", utf8_decode($qchoice[0]));
	    
	    printf("<ol>\n");
	    foreach($qchoice[1] as $choice => $correct) {
		printf("<li>%s</li>\n", utf8_decode($choice));
	    }
	    printf("</ol>\n");
	    
	    printf("%s: %s\n", _("Correct answer"), implode(", ", array_keys($qchoice[1], true)));
	}
	fprintf($stream, "<p>[%s: %.01f]</p>\n", _("Max score"), $question->getQuestionScore());
	
	// 
	// The answer together with comment (if set).
	// 
	if(isset($answer)) {
	    fprintf($stream, "<h3>%s</h3>\n", _("Answer"));
	    if($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
		fprintf($stream, "<p>%s</p>\n", utf8_decode($answer->getAnswerText()));
	    } elseif($question->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE ||
		     $question->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
		$achoice = Exam::getQuestionChoice($answer->getQuestionText());
		fprintf($stream, "<p>%s</p>\n", implode(", ", $achoice[1]));
	    }
	    if($answer->hasAnswerComment()) {
		fprintf($stream, "%s: %s<br/>\n", _("Your comments"), $answer->getAnswerComment());
	    }
	    if($answer->hasResultComment()) {
		fprintf($stream, "%s: %s<br/>\n", _("Teacher comments"), $answer->getResultComment());
	    }
	    fprintf($stream, "<p>[%s: %.01f]</p>\n", _("Score"), $answer->getResultScore());
	}
    }
    
    // 
    // Output the question/answer section.
    // 
    private function printSectionAnswers($stream)
    {	
	$handler   = new Correct($this->exam->getExamID());
	$answers   = $handler->getStudentAnswers($this->student);
	$questions = $this->manager->getQuestions();
	
	//
	// Build the assiciative array of answered ('a') and unanswered ('u') questions.
	//
	$qadata = array( 'a' => array(), 'u' => array() );
	foreach($questions as $question) {
	    $answered = false;
	    foreach($answers as $answer) {
		if($question->getQuestionID() == $answer->getQuestionID()) {
		    $answered = true;
		    break;
		}
	    }
	    if($answered) {
		$qadata['a'][] = array($question, $answer);
	    } else {
		$qadata['u'][] = array($question, null);
	    }
	}

	// 
	// Now loop thru the array of questions and answers. We use two separate 
	// sections for answered and unanswered questions.
	// 

	if(count($qadata['a']) > 0) {
	    fprintf($stream, "<h1>%s</h1>\n", _("Questions and Answers"));
	    foreach($qadata['a'] as $data) {
		$this->printQuestionAnswer($stream, $data[0], $data[1]);
	    }
	}
	
	if(count($qadata['u']) > 0) {
	    fprintf($stream, "<h1>%s</h1>\n", _("Unanswered Questions"));
	    foreach($qadata['u'] as $data) {
		$this->printQuestionAnswer($stream, $data[0], null);
	    }
	}
    }
    
    // 
    // Print sub section for examination creator.
    // 
    private function printSubSectionExamCreator($stream)
    {
	$this->ldap->setAttributeFilter(array("cn", "mail"));
	$user = $this->ldap->searchPrincipalName($this->exam->getExamCreator());
	
	fprintf($stream, "<h5>%s:</h5>\n", _("Responsable for examination"));
	fprintf($stream, 
		"<p><blockquote>" . 
		_("Main responsible person for the examination is <a href=\"mailto:%s\">%s</a>") . 
		"</blockquote></p>\n",
		$user->first()->getMail(), 
		utf8_decode($user->first()->getCN()->first()));
	$this->ldap->setAttributeFilter(null);
    }

    // 
    // Print sub section for question contributors and answer correctors.
    // 
    private function printSubSectionContributors($stream)
    {
	$this->ldap->setAttributeFilter(array("cn", "mail"));
	
	$contributors = $this->manager->getContributors();
 	if($contributors->count()) {
	    
	    // 
	    // Collect contributors first:
	    // 
	    $qdata = array();
	    $questions = $this->manager->getQuestions();
	    foreach($contributors as $contributor) {
		foreach($questions as $question) {
		    if($question->getQuestionPublisher() == $contributor->getContributorUser()) {
			$uuid = $contributor->getContributorUser();
			$name = $question->getQuestionName();
			$qdata[$uuid][] = trim($name, ".");
		    }
		}
	    }
	    
	    // 
	    // Output the list of contributors:
	    // 
	    fprintf($stream, "<h5>%s:</h5>\n", _("Correction of answers"));
	    fprintf($stream, 
		    "<p><blockquote>" . 
		    _("These people have contributed questions for the examination and corrected answers.") . 
		    "</blockquote></p>\n");
	    fprintf($stream, "<ul>\n");
	    foreach($qdata as $uuid => $qlist) {
		$user = $this->ldap->searchPrincipalName($uuid);
		fprintf($stream, "<li><a href=\"mailto:%s\">%s</a><br/><blockquote>%s: %s</blockquote></li>\n",
			$user->first()->getMail(), 
			utf8_decode($user->first()->getCN()->first()),
			_("Corrected questions"), implode(", ", $qlist));
	    }
	    fprintf($stream, "</ul>\n");
	}
	$this->ldap->setAttributeFilter(null);
    }
    
    // 
    // Output the reference section.
    // 
    private function printSectionReference($stream)
    {
	$openexam = "http://it.bmc.uu.se/andlov/proj/openexam/";
	$htmldoc  = "http://www.easysw.com/htmldoc/";
	
	fprintf($stream, "<h1>%s</h1>\n", _("Reference"));
	fprintf($stream, "<p>" . _("Information about this examination.") . "</p>\n");

	fprintf($stream, "<h2>%s</h2>\n", _("Contact Information"));
	fprintf($stream, "<p>" . _("This section lists those people thats been involved in this examination.") . "</p>\n");

	// 
	// Print examination creator and question contributors:
	// 
	$this->printSubSectionExamCreator($stream);
	$this->printSubSectionContributors($stream);

	// 
	// Print document info:
	// 
	fprintf($stream, "<h2>%s</h2>\n", _("Document Information"));
	fprintf($stream, "<p>" . 
		_("This document was generated by <a href=\"%s\">OpenExam PHP</a> installed on <a href=\"%s\">%s</a> using the open source version of the <a href=\"%s\">HTMLDOC PostScript&#0174;/PDF generator</a> released under GPL license.") . 
		_("OpenExam PHP is a secure examination system developed at Uppsala Biomedical Centre, Uppsala University.") .
		"</p>\n", 
		$openexam, BASE_URL, BASE_URL, $htmldoc);
    }
    
    // 
    // This is our callback function implementing the interface InputGenerator.
    // 
    public function write($stream)
    {
	fprintf($stream, "<html>\n<head><title>%s</title></head>\n<body>\n", $this->title);
	$this->printMetaData($stream);
	$this->printSectionDescription($stream);
	$this->printSectionScores($stream);
	$this->printSectionAnswers($stream);
	$this->printSectionReference($stream);
	fprintf($stream, "</body></html>\n");
    }
    
    // 
    // Feed the HTMLDOC process with HTML (*.book) on stdin. If debug mode is enabled, 
    // then no process is executed, instead the generated HTML is output on stdout.
    // 
    private function generate($target = null)
    {
	$this->ldap->setAttributeFilter(array( "cn" ));
	
	// 
	// We must create the frontpage before launching the process.
	// 
	$data = $this->manager->getStudentData($this->student);
	$user = $this->ldap->searchUID($data->getStudentUser());
	if(!$user->first()->hasCN()) {
	    $user = $this->ldap->searchPrincipalName($data->getStudentUser());
	}
	if($user->first()->hasCN()) {
	    $name = $user->first()->getCN()->first();
	} else {
	    $name = "";
	}
	
	if(file_exists($this->frontpage)) {
	    unlink($this->frontpage);
	}
	$this->frontpage = tempnam("/tmp", "openexam-result");
	$this->createFrontPage($name);

	$this->begin($this, $target);
    }
    
    // 
    // Get result from database and send the output to the browser.
    // 
    public function send($student)
    {
	$data = $this->manager->getStudentData($student);
	
	if(strstr($this->format, "pdf")) {
	    header("Content-Type: application/pdf");
	    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
			   sprintf("%s-%s.pdf", $data->getStudentUser(), $data->getStudentCode())));	    
	} elseif(strstr($this->format, "ps")) {
	    header("Content-Type: application/postscript");
	    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
			   sprintf("%s-%s.ps", $data->getStudentUser(), $data->getStudentCode())));	    
	} elseif(strstr($this->format, "html")) {
	    header("Content-Type: text/html");
	    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
			   sprintf("%s-%s.html", $data->getStudentUser(), $data->getStudentCode())));	    
	}
	
	$this->student = $student;
	$this->generate();
    }
    
    // 
    // Get result from the database and save it to output file.
    // 
    public function save($student, $file)
    {
	$this->student = $student;
	$this->generate($file);
    }
    
    // 
    // Save all student results for this examination. Note that
    // the destination must be a writable directory.
    // 
    public function saveAll($destdir)
    {
	$students = $this->manager->getStudents();
	foreach($students as $student) {
	    if(strstr($this->format, "pdf")) {
		$file = sprintf("%s/%s-%s.pdf", 
				$destdir, 
				$student->getStudentUser(), 
				$student->getStudentCode());
	    } elseif(strstr($this->format, "ps")) {
		$file = sprintf("%s/%s-%s.ps", 
				$destdir,
				$student->getStudentUser(),
				$student->getStudentCode());
	    } elseif(strstr($this->format, "html")) {
		$file = sprintf("%s/%s-%s.html", 
				$destdir, 
				$student->getStudentUser(), 
				$student->getStudentCode());
	    } else {
		throw new Exception(sprintf("Unknown format '%s'", $this->format));
	    }
	    
	    $this->student = $student->getStudentID();
	    $this->generate($file);
	}
    }

    // 
    // Send all results as a zip-file.
    // 
    public function sendAll()
    {
	$dirname = tempnam("/tmp", "openexam-result");
	$zipname = tempnam("/tmp", "openexam-result");

	if(!$dirname || !$zipname) {
	    throw new Exception("Failed create temporary file.");
	}

	// 
	// For naming the result zip-file. Note that we use RFC 2822 format of date:
	// 
	$data = $this->manager->getData();
	$name = sprintf("%s - %s", $data->getExamName(), date('r', strtotime($data->getExamStartTime())));
	
	$zip = new ZipArchive();
	if(!$zip->open($zipname, ZIPARCHIVE::OVERWRITE)) {
	    throw new Exception("Failed create zip-archive.");
	}
	unlink($dirname);
	if(!mkdir($dirname)) {
	    throw new Exception("Failed create temporary directory.");	    
	}

	$this->saveAll($dirname);
	if(!($handle = opendir($dirname))) {
	    throw new Exception("Failed open zip entry directory.");
	}
	
	// 
	// Must add all files to zip-file and close the zip before unlinking the
	// temporary files added to zip-file, otherwise we get an empty archive.
	// 
	while(($file = readdir($handle)) !== false) {
	    if($file == "." || $file == "..") {
		continue;
	    }
	    $path = sprintf("%s/%s", $dirname, $file);
	    $zip->addFile($path, $file);
	}
	$zip->close();
	rewinddir($handle);
	while(($file = readdir($handle)) !== false) {
	    if($file != "." && $file == "..") {
		$path = sprintf("%s/%s", $dirname, $file);
		unlink($path);
	    }
	}
	closedir($handle);
	
	header("Content-Type: application/zip");
	header(sprintf("Content-Disposition: attachment;filename=\"%s.zip\"", $name));
	header("Cache-Control: no-cache");
	header("Pragma-directive: no-cache");
	header("Cache-directive: no-cache");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	readfile($zipname);
	
	if(file_exists($zipname)) {
	    unlink($zipname);
	}
	if(file_exists($dirname)) {
	    rmdir($dirname);
	}
    }
    
}

?>
