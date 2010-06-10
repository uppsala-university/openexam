<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/scoreboard.inc
// Author: Anders Lövgren
// Date:   2010-05-28
// 
// Classes for working with score board data.
// 


// 
// This class represent the summary and total (max) score for a
// single row or column.
// 
class Score
{
    private $sum;
    private $max;
    private $num;     // Number of values added
    
    public function __construct($sum, $max)
    {
	$this->sum = $sum;
	$this->max = $max;
	$this->num = 0;
    }
    
    public function getSum() 
    {
	return $this->sum;
    }
    
    public function getMax()
    {
	return $this->max;
    }
    
    public function getNum()
    {
	return $this->num;
    }
    
    public function addSum($value)
    {
	$this->sum += $value;
	$this->num++;
    }
    
    public function addMax($value)
    {
	$this->max += $value;
    }
    
    public function getPercent()
    {
	if($this->num == 0) {
	    return 0;
	}
	return 100 * $this->sum / $this->max;
    }
    
    public function getAvarage()
    {
	if($this->num == 0) {
	    return 0;
	}
	return $this->sum / $this->num;
    }
}

// 
// This class is an abstraction of the score board for one exam.
// 
class ScoreBoard
{
    private $students;
    private $questions;
    private $scores = array();    // Two dimensional array
    private $exam;
    private $maxscore;            // The maximum score on this exam
    
    public function __construct($exam)
    {
	$this->initilize($exam);
	$this->exam = $exam;
    }
    
    public function getQuestions() 
    {
	return $this->questions;
    }
    
    public function getStudents()
    {
	return $this->students;
    }

    public function getData($student, $question) 
    {
	if(!isset($this->scores[$student][$question])) {
	    return null;
	}
	return $this->scores[$student][$question];
    }
    
    public function getExam()
    {
	if(!isset($this->data)) {
	    $manager = new Manager($this->exam);
	    $this->data = $manager->getData();
	}
	return $this->data;
    }
    
    private function initilize($exam)
    {
	$manager = new Manager($exam);
	
	$this->questions = $manager->getQuestions();
	$this->students  = $manager->getStudents();
	
	$correct = new Correct($exam);
	$data = $correct->getScoreBoard();
	
	foreach($data as $record) {
	    $this->scores[$record->getStudentID()][$record->getQuestionID()] = $record;
	}
    }

    // 
    // Utility functions:
    // 
    
    public function getStudentScore($student)
    {
	$score = new Score(0, 0);
	if(isset($this->scores[$student])) {
	    foreach($this->scores[$student] as $question => $record) {
		if($record->getQuestionStatus() == 'active') {
		    if($record->getResultScore() != null) {
			$score->addSum($record->getResultScore());
		    }
		    $score->addMax($record->getQuestionScore());
		}
	    }
	}
	return $score;
    }

    public function getQuestionScore($question)
    {
	$score = new Score(0, 0);
	foreach($this->scores as $student => $data) {
	    if(isset($data[$question])) {
		$record = $data[$question];
		if($record->getQuestionStatus() == 'active') {
		    if($record->getResultScore() != null) {
			$score->addSum($record->getResultScore());
		    }
		    $score->addMax($record->getQuestionScore());
		}
	    }
	}
	return $score;
    } 
    
    public function getMaximumScore()
    {
	if(!isset($this->maxscore)) {
	    $total = 0;
	    foreach($this->questions as $question) {
		if($question->getQuestionStatus() == 'active') {
		    $total += $question->getQuestionScore();
		}
	    }
	    $this->maxscore = $total;
	}
	return $this->maxscore;
    }
}

// 
// Output formatters:
// 
abstract class OutputFormatter
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
    
    abstract public function getMimeType();
    abstract public function getExtension();
    
    public function getHeader() { return ""; }
    public function getFooter() { return ""; }
}

class OutputTextTab extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
        return implode("\t", $this->values);
    }
    
    public function getMimeType()
    {
	return "text/tab-separated-values";
    }
    
    public function getExtension()
    {
	return "tab";
    }
}

class OutputTextCsv extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
	return "\"" . implode("\",\"", $this->values) . "\"";
    }
    
    public function getMimeType()
    {
	return "text/csv";
    }
    
    public function getExtension()
    {
	return "csv";
    }
}

class OutputTextXml extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
        return "<row><data>" . implode("</data><data>", $this->values) . "</data></row>";
    }

    public function getMimeType()
    {
	return "application/xml";
    }
    
    public function getExtension()
    {
	return "xml";
    }

    public function getHeader() 
    { 
	return "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n<rows>\n";
    }
    
    public function getFooter() 
    { 
	return "</rows>\n"; 
    }
}

class OutputTextHtml extends OutputFormatter
{
    public function getLine(&$data)
    {
	parent::format($data);
	return "<tr><td>" . implode("</td><td>", $this->values) . "</td></tr>";
    }

    public function getMimeType()
    {
	return "text/html";
    }
    
    public function getExtension()
    {
	return "html";
    }

    public function getHeader() 
    {
	return "<html>\n<head></head>\n<body>\n<table>\n"; 
    }
    
    public function getFooter() 
    { 
	return "</table>\n</body>\n</html>\n"; 
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
    
    public function writeHeader()
    {
	fprintf($this->stream, "%s", $this->format->getHeader());
    }
    
    public function writeFooter()
    {
	fprintf($this->stream, "%s", $this->format->getFooter());	
    }
}

// 
// The base class for all sub classes that output score board data.
// 
abstract class ScoreBoardOutput
{
    protected $board;    // The score board
    private $ldap;       // For LDAP search
    
    public function __construct($exam)
    {
	$this->board = new ScoreBoard($exam);
    }
    
    public function __destruct()
    {
	if(isset($this->ldap)) {
	    $this->ldap->close();
	}
    }
    
    // 
    // Support function for doing LDAP lookup.
    // 
    protected function getCommonName($user, $lookup = LDAP_LOOKUP_BOTH)
    {
	if(!isset($this->ldap)) {
	    $this->ldap = LDAPSearch::factory();
	}
	$this->ldap->setAttributeFilter(array("cn"));
	
 	if($lookup & LDAP_LOOKUP_UID) {	    
	    $entry = $this->ldap->searchUID($user);
	    if($entry->first()->hasCN()) {		
		return $entry->first()->getCN()->first();
	    }
	}
	if($lookup & LDAP_LOOKUP_PRINCIPAL) {
	    $entry = $this->ldap->searchPrincipalName($user);
	    if($entry->first()->hasCN()) {
		return $entry->first()->getCN()->first();
	    }
	}
	return $user;
    }
}

// 
// Writes an score board to output stream. The output exposes the real 
// names, so it should only be used from the decoder page. The purpose
// of this class is to write the score board in different formats.
// 
class ScoreBoardWriter extends ScoreBoardOutput
{    
    private $writer;      // The stream writer
    private $format;      // The output formatter
    
    public function __construct($exam, $writer, $format)
    {
	parent::__construct($exam);
	
	$this->writer = $writer;
	$this->format = $format;
    }

    // 
    // Set new format object.
    // 
    public function setFormat($format)
    {
	$this->format = $format;
    }
    
    // 
    // Set new writer object.
    // 
    public function setWriter($writer)
    {
	$this->writer = $writer;
    }
    
    // 
    // Save to file using a local opened stream writer.
    // 
    public function save($file)
    {
	$stream = fopen($file, "w");
	$writer = $this->writer;
	$this->writer = new StreamWriter(stream, $this->format);
	$this->send(false);
	$this->writer = $writer;
	fclose($stream);
    }
    
    // 
    // Send to output, with optional HTTP headers.
    // 
    public function send($content = true)
    {
	$this->writer->writeHeader();
	$this->write();
	$this->writer->writeFooter();
	
	if($content) {
	    header(sprintf("Content-Type: %s", 
			   $this->format->getMimeType()));
	    header(sprintf("Content-Disposition: attachment;filename=\"%s.%s\"", 
			   $this->board->getExam()->getExamName(), 
			   $this->format->getExtension()));
	    header("Cache-Control: no-cache");
	    header("Pragma-directive: no-cache");
	    header("Cache-directive: no-cache");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	}
	
	rewind($this->writer->getStream());
	echo stream_get_contents($this->writer->getStream());
    }
    
    // 
    // Format the score table using the supplied formatter object.
    // 
    private function write()
    {
	$questions = $this->board->getQuestions();

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
	$this->writer->writeLine($array);
	
	// 
	// Output the list of students.
	// 
	$students = $this->board->getStudents();
	foreach($students as $student) {
	    $array = array();
	    $student->setStudentName(utf8_decode($this->getCommonName($student->getStudentUser())));
	    $array[] = $student->getStudentName();
	    $array[] = $student->getStudentUser();
	    $array[] = $student->getStudentCode();
	    foreach($questions as $question) {
		$data = $this->board->getData($student->getStudentID(), $question->getQuestionID());
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
	    $score = $this->board->getStudentScore($student->getStudentID());
	    $array[] = $score->getSum();
	    $array[] = $score->getMax();
	    $array[] = $this->board->getMaximumScore();
	    $array[] = 100 * $score->getSum() / $this->board->getMaximumScore();
	    $this->writer->writeLine($array);
	}
    }
}

// 
// This class outputs the score board data as HTML, but is intended to be 
// called from different places and adopts the output depending on whether
// the examination is decoded or not.
// 
class ScoreBoardPrinter extends ScoreBoardOutput
{
    private $decoded;     // Allready decoded
    private $verbose;     // Be more verbose in output.
    private $colorize;    // Colorize scores.
    private $show;
    
    public function __construct($exam)
    {
	parent::__construct($exam);	
	$this->decoded = $this->board->getExam()->getExamDecoded() == 'Y';
	$this->verbose = false;
	$this->colorize = false;
    }
    
    public function setVerbose($bool)
    {
	$this->verbose = $bool;
    }
    
    public function setColorized($bool)
    {
	$this->colorize = $bool;
    }
    
    public function output()
    {
	$this->index = 0;
	$questions = $this->board->getQuestions();
	
	// 
	// Select which field to output:
	// 
	$this->show->name = $this->decoded;
	$this->show->user = $this->decoded && $this->verbose;
	$this->show->code = $this->verbose || !$this->decoded;
	$this->show->summary = $this->verbose;
	$this->show->percent = true;
	$this->show->avarage = true;
	$this->show->grade = true;
	$this->show->score = true;
	$this->show->links = !$this->decoded;
	
	$table = new Table();

	// 
	// Print headers:
	// 
	$row = $table->addRow();
	if($this->show->name) {
	    $row->addHeader(_("Name"));
	}
	if($this->show->user) {
	    $row->addHeader(_("User"));
	}
	if($this->show->code) {
	    $row->addHeader(_("Code"));
	}
	if($this->show->score) {
	    foreach($questions as $question) {
		$header = $this->getHeaderData($question);
		$obj = $row->addHeader($header->name);
		if($this->show->links && isset($header->link) && $question->getQuestionStatus() != 'removed') {
		    $obj->setLink($header->link)->setTitle($header->desc);
		} else {
		    $obj->setAnchor($header->name, $header->desc);
		}
	    }
	}
	if($this->show->summary) {
	    $row->addHeader(_("Summary"));
	}
	if($this->show->percent) {
	    $row->addHeader(_("Percent"));
	}
	if($this->show->grade) {
	    $row->addHeader(_("Grade"));
	}

	// 
	// Print all students:
	// 	
	$students = $this->board->getStudents();
	$grades = new ExamGrades($this->board->getExam()->getExamGrades());
	foreach($students as $student) {
	    $row = $table->addRow();
	    if($this->show->name) {
		$obj = $row->addData(utf8_decode($this->getCommonName($student->getStudentUser())));
		$obj->setNoWrap();
		if(!$this->show->user && !$this->show->code) {
		    $obj->setTitle(sprintf("%s: %s\n%s: %s", 
					   _("User"), $student->getStudentUser(),
					   _("Code"), $student->getStudentCode()));
		}
	    }
	    if($this->show->user) {
		$row->addData($student->getStudentUser());
	    }
	    if($this->show->code) {
		$obj = $row->addData($student->getStudentCode());
		if($this->show->links) {
		    $obj->setLink(sprintf("?exam=%d&amp;action=correct&amp;student=%d",
					  $student->getExamID(), 
					  $student->getStudentID()));
		}
	    }
	    if($this->show->score) {
		// 
		// Print all answers for this student:
		// 
		foreach($questions as $question) {
		    $data = $this->board->getData($student->getStudentID(), 
						  $question->getQuestionID());
		    if($this->colorize) {
			if(!isset($data)) {
			    $obj = $row->addData("?");
			    $obj->setClass("cc s0");
			} elseif($data->hasResultScore()) {
			    $cls = $this->getColorClass($data->getResultScore(), $data->getQuestionScore());
			    $obj = $row->addData(sprintf("%.01f", $data->getResultScore()));
			    $obj->setClass(sprintf("cc s%d", $cls));
			} elseif($data->getQuestionStatus() == 'removed') {
			    $obj = $row->addData("-");
			    $obj->setClass("cc qr");
			} else {
			    $obj = $row->addData("X");
			    $obj->setClass("cc nc");
			}
		    } else {
			if(!isset($data)) {
			    $obj = $row->addData("?");
			    $obj->setClass("cc na");
 			} elseif($data->getQuestionStatus() == 'removed') {
			    $obj = $row->addData(sprintf("%.01f", $data->getResultScore()));
			    $obj->setClass("cc qr");
			} elseif($data->hasResultScore()) {
			    $obj = $row->addData(sprintf("%.01f", $data->getResultScore()));
			    $obj->setClass("cc ac");
			} else {
			    $obj = $row->addData("X");
			    $obj->setClass("cc nc");
			}
		    }
		    
		    // 
		    // Theres no reason to continue if data is missing or if exam is decoded.
		    // 
		    if(!isset($data) || $this->decoded) {
			continue;
		    }
		    
		    if($question->getQuestionStatus() != 'removed') {
			if($question->getQuestionPublisher() == phpCAS::getUser()) {
			    $obj->setLink(sprintf("?exam=%d&amp;action=correct&amp;answer=%d",
						  $data->getExamID(), 
						  $data->getAnswerID()));
			} else {
			    if(!$this->colorize) {
				$obj->setClass("cc no");
			    }
			}
		    }
		}
	    }
	    if($this->show->summary ||
	       $this->show->percent ||
	       $this->show->grade) {
		$score = $this->board->getStudentScore($student->getStudentID());
		$grade = $grades->getGrade($score->getSum());
		if($this->show->summary) {
		    $obj = $row->addData(sprintf("%.01f/%.01f/%.01f", $score->getSum(), $score->getMax(), $this->board->getMaximumScore()));
		}
		if($this->show->percent) {
		    $obj = $row->addData(sprintf("%.01f%%", 100 * $score->getSum() / $this->board->getMaximumScore()));
		}
		if($this->show->grade) {
		    $obj = $row->addData($grade);
		    $obj->setClass(sprintf("gr%s", strtolower($grade)));
		}
	    }
	}
	// 
	// Output answer avarage values at bottom.
	// 
	$table->addRow();
	$row = $table->addRow();
	$row->setClass("avarage");
	if($this->decoded) {
	    if($this->show->user) {
		$row->addData();
	    }
	    if($this->show->code) {
		$row->addData();
	    }
	}
	$obj = $row->addHeader(_("Avarage:"));
	$obj->setAlign(TABLE_ALIGN_RIGHT);
	
	foreach($questions as $question) {
	    $score = $this->board->getQuestionScore($question->getQuestionID());
	    if($score->getNum() == 0) {
		$obj = $row->addData("-");
	    } else {
		$obj = $row->addData(sprintf("%.01f", $score->getSum() / $score->getNum()));
	    }
	    if($this->colorize) {
		$cls = $this->getColorClass($score->getSum(), $score->getMax());
		$obj->setClass(sprintf("cc s%d", $cls));
	    } else {
		$obj->setClass("cc aa");
	    }
	    $obj->setTitle(sprintf("%s: %d\n%s: %.01f%%", 
				   _("Answers"), $score->getNum(),
				   _("Percent"), $score->getPercent()));
	}
	
	$table->output();
    }

    // 
    // Get the color class for this cell (when colorized).
    // 
    private function getColorClass($val, $max)
    {
	if($max == 0) {
	    return 0;
	}
	return 20 * floor(5 * $val / $max);
    }
    
    private function getHeaderData($question)
    {
	if($question->getQuestionPublisher() == phpCAS::getUser()) {
	    $header->link = sprintf("?exam=%d&amp;action=correct&amp;question=%d",
				    $question->getExamID(),
				    $question->getQuestionID());
	}
	$header->name = sprintf("Q%d", ++$this->index);
	$header->desc = sprintf("%s %s\n\n%s\n\n%s: %.01f\n%s: %s",
				_("Question"),
				utf8_decode($question->getQuestionName()),
				utf8_decode($question->getQuestionText()),
				_("Max score"),
				$question->getQuestionScore(),
				_("Publisher"),
				utf8_decode($this->getCommonName($question->getQuestionPublisher())));
	if($question->getQuestionStatus() == 'removed') {
	    if($question->getQuestionComment() != null) {
		$header->desc .= sprintf("\n\n%s: %s", 
					 _("This question has been removed"),
					 utf8_decode($question->getQuestionComment()));
	    } else {
		$header->desc .= sprintf("\n\n%s.", 
					 _("This question has been removed"));
	    }
	}
	return $header;
    }
}

?>
