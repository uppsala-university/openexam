<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/pdf.inc
// Author: Anders Lövgren
// Date:   2010-05-12
// 
// Support for generating PDF files. Currently, the application HTMLDOC is 
// used for converting structured HTML (caller *.book files) to PDF thru a
// UNIX command pipeline.
// 

if(!defined('PDF_ENGINE')) {
    define ('PDF_ENGINE', 'htmldoc');
}
if(!defined('PDF_LOGOTYPE')) {
    define ('PDF_LOGOTYPE',  '../images/uu-logotype-4f-84.png');
}
if(!defined('PDF_LINKCOLOR')) {
    define ('PDF_LINKCOLOR', '#000099');
}
if(!defined('PDF_LINKSTYLE')) {
    define ('PDF_LINKSTYLE', 'plain');
}
if(!defined('PDF_PAGESIZE')) {
    define ('PDF_PAGESIZE',  'a4');
}
if(!defined('PDF_GENERATOR')) {
    define ('PDF_GENERATOR', 'OpenExam PHP');
}
if(!defined('PDF_OUTPUT_FORMAT')) {
    define ('PDF_OUTPUT_FORMAT', 'pdf14');
}

class HtmlDocPDF
{
    protected $title;
    protected $author;
    protected $debug = false;
    protected $format = PDF_OUTPUT_FORMAT;
    
    protected function __construct($title, $author)
    {
	$this->title  = $title;
	$this->author = $author;
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
    protected function getCommand($target = null)
    {	
	$fmt = "htmldoc - --book --title --format %s --linkstyle %s --size %s --titleimage %s --linkcolor '%s'";
	$cmd = sprintf($fmt, $this->format, PDF_LINKSTYLE, PDF_PAGESIZE, PDF_LOGOTYPE, PDF_LINKCOLOR);
	if(isset($target)) {
	    $cmd .= sprintf(" -f %s", $target);
	}
	return $cmd;
    }
}

// 
// The class for generating a PDF file containing the result of
// an exam for a single student.
// 
class ResultPDF extends HtmlDocPDF
{
    private $manager;
    private $exam;
    
    public function __construct($examid)
    {
	$this->manager = new Manager($examid);
	$this->exam = $this->manager->getData();
	
	parent::__construct($this->exam->getExamName(), 
			    $this->exam->getExamOrgUnit());
    }

    // 
    // Output meta data.
    // 
    private function printMetaData($stream)
    {
	fprintf($stream, "<!-- FOOTER CENTER \"\$CHAPTER\" -->\n");
	fprintf($stream, "<!-- FOOTER LEFT \"%s\" -->\n", _('Page $PAGE of $PAGES'));
	fprintf($stream, "<!-- FOOTER RIGHT \"\$DATE \$TIME\" -->\n");
	  
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
    // Output the question/answer section.
    // 
    private function printSectionAnswers($stream, $student)
    {
	fprintf($stream, "<h1>%s</h1>\n", _("Questions"));
	
	$handler = new Correct($this->exam->getExamID());
	$answers = $handler->getStudentAnswers($student);
	foreach($answers as $answer) {
	    $question = Exam::getQuestionData($answer->getQuestionID());
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
	    // The answer together with comment.
	    // 
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
    // Feed the HTMLDOC process with HTML (*.book) on stdin. If debug mode is enabled, 
    // then no process is executed, instead the generated HTML is output on stdout.
    // 
    private function generate($student, $cmd)
    {
	if($this->debug) {
	    $pipes = array( fopen("php://stdout", "w") );
	} else {
	    $fds = array( 
			  0 => array("pipe", "r"),
			  1 => array("pipe", "w"),
			  2 => array("pipe", "w")
			  );
	    
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
	    // 
	    // Output HTML header and body with meta data. Include questions/answer and 
	    // summary sections.
	    // 
	    fprintf($pipes[0], "<html>\n<head><title>%s</title></head>\n<body>\n", $this->title);
	    $this->printMetaData($pipes[0]);
	    $this->printSectionDescription($pipes[0]);
	    $this->printSectionAnswers($pipes[0], $student);
	    fprintf($pipes[0], "</body></html>\n");
	    
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
    
    // 
    // Get result from database and send the output to the browser.
    // 
    public function send($student)
    {
	$students = $this->manager->getStudents();
	foreach($students as $data) {
	    if($data->getStudentID() == $student) {
		if(strstr($this->format, "pdf")) {
		    header("Content-Type: application-pdf");
		    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
				   sprintf("%s-%s.pdf", $data->getStudentUser(), $data->getStudentCode())));	    
		} elseif(strstr($this->format, "ps")) {
		    header("Content-Type: application-postscript");
		    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
				   sprintf("%s-%s.ps", $data->getStudentUser(), $data->getStudentCode())));	    
		} elseif(strstr($this->format, "html")) {
		    header("Content-Type: text/html");
		    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", 
				   sprintf("%s-%s.html", $data->getStudentUser(), $data->getStudentCode())));	    
		}
		$cmd = $this->getCommand();
		$this->generate($student, $cmd);
		
		return true;
	    }
	}
	return false;
    }
    
    // 
    // Get result from the database and save it to output file.
    // 
    public function save($student, $file)
    {
	$cmd = $this->getCommand($file);
	$this->generate($student, $cmd);	
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
	    $this->save($student->getStudentID(), $file);
	}
    }
    
}

?>
