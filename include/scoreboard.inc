<?php

// 
// Copyright (C) 2010, 2014 Computing Department BMC, 
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
                if ($this->num == 0 || $this->max == 0) {
                        return 0;
                }
                return 100 * $this->sum / $this->max;
        }

        public function getAvarage()
        {
                if ($this->num == 0) {
                        return 0;
                }
                return $this->sum / $this->num;
        }

}

// 
// Filters on what to return from a scoreboard.
// 
class ScoreBoardFilter
{

        private $exam;
        private $others;        // Include questions assigned to others.
        private $unattended;    // Include unattended students on exam.
        private $questions;
        private $students;

        public function __construct($exam, $others, $unattended)
        {
                $this->exam = $exam;
                $this->others = $others;
                $this->unattended = $unattended;
        }

        public function questions($questions)
        {
                if ($this->others) {
                        return $questions;
                }
                if (isset($this->questions)) {
                        return $this->questions;
                }

                $result = array();
                foreach ($questions as $question) {
                        if ($question->getQuestionPublisher() == phpCAS::getUser()) {
                                $result[] = $question->data();
                        }
                }

                $this->questions = new ResultSet($result);     // Cache
                return $this->questions;
        }

        public function students($students)
        {
                if ($this->unattended) {
                        return $students;
                }
                if (isset($this->students)) {
                        return $this->students;
                }

                $correct = new Correct($this->exam);

                $result = array();
                foreach ($students as $student) {
                        $answers = $correct->getStudentAnswers($student->getStudentID());
                        if ($answers->count() != 0) {
                                $result[] = $student->data();
                        }
                }

                $this->students = new ResultSet($result);      // Cache
                return $this->students;
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
        private $maxscore = array();  // The maximum score on this exam
        private $dugga = false;       // The exam is a dugga (randomized)
        private $filter;              // Filter returned students and questions.

        public function __construct($exam, $filter)
        {
                $this->initilize($exam);
                $this->exam = $exam;
                $this->filter = $filter;
        }

        public function getQuestions()
        {
                return $this->filter->questions($this->questions);
        }

        public function getStudents()
        {
                return $this->filter->students($this->students);
        }

        public function getData($student, $question)
        {
                if (!isset($this->scores[$student][$question])) {
                        return null;
                }
                return $this->scores[$student][$question];
        }

        public function getExam()
        {
                if (!isset($this->data)) {
                        $manager = new Manager($this->exam);
                        $this->data = $manager->getData();
                }
                return $this->data;
        }

        private function initilize($exam)
        {
                $manager = new Manager($exam);

                $this->questions = $manager->getQuestions();
                $this->students = $manager->getStudents();
                $this->dugga = $manager->isDugga();

                $correct = new Correct($exam);
                $data = $correct->getScoreBoard();

                foreach ($data as $record) {
                        $this->scores[$record->getStudentID()][$record->getQuestionID()] = $record;
                }
        }

        //
        // Utility functions:
        //

        public function getStudentScore($student)
        {
                $score = new Score(0, 0);
                if (isset($this->scores[$student])) {
                        foreach ($this->scores[$student] as $question => $record) {
                                if ($record->getQuestionStatus() == 'active') {
                                        if ($record->getResultScore() != null) {
                                                $score->addSum($record->getResultScore());
                                        }
                                        if ($record->getAnswerExist() != 'N') {
                                                $score->addMax($record->getQuestionScore());
                                        }
                                }
                        }
                }
                return $score;
        }

        //
        // The maximum score for this student counting all questions, even
        // those without an answer.
        // 
        public function getStudentMaximum($student)
        {
                if (!isset($this->maxscore[$student])) {
                        $total = 0;
                        foreach ($this->questions as $question) {
                                if ($question->getQuestionStatus() == 'active') {
                                        if (isset($this->scores[$student][$question->getQuestionID()])) {
                                                $total += $question->getQuestionScore();
                                        }
                                }
                        }
                        $this->maxscore[$student] = $total;
                }
                return $this->maxscore[$student];
        }

        public function getQuestionScore($question)
        {
                $score = new Score(0, 0);
                foreach ($this->scores as $student => $data) {
                        if (isset($data[$question])) {
                                $record = $data[$question];
                                if ($record->getQuestionStatus() == 'active') {
                                        if ($record->getResultScore() != null) {
                                                $score->addSum($record->getResultScore());
                                        }
                                        $score->addMax($record->getQuestionScore());
                                }
                        }
                }
                return $score;
        }

        //
        // The maximum score of all questions. If the exam is a dugga, then the
        // maximum score is based on the maximum possible score.
        //
        public function getMaximumScore()
        {
                if (!isset($this->maxscore['total'])) {
                        $total = 0;
                        if ($this->dugga) {
                                $scores = array();
                                foreach ($this->questions as $question) {
                                        if ($question->getQuestionStatus() == 'active') {
                                                if ($question->getTopicRandom() != 0) {
                                                        if (!isset($scores[$question->getTopicID()])) {
                                                                $scores[$question->getTopicID()] = $question->getTopicRandom() * $question->getQuestionScore();
                                                        }
                                                } else {
                                                        if (!isset($scores[$question->getTopicID()])) {
                                                                $scores[$question->getTopicID()] = 0;
                                                        }
                                                        $scores[$question->getTopicID()] += $question->getQuestionScore();
                                                }
                                        }
                                }
                                $total = array_sum($scores);
                        } else {
                                foreach ($this->questions as $question) {
                                        if ($question->getQuestionStatus() == 'active') {
                                                $total += $question->getQuestionScore();
                                        }
                                }
                        }
                        $this->maxscore['total'] = $total;
                }
                return $this->maxscore['total'];
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
                foreach ($data as $value) {
                        if (is_float($value)) {
                                $this->values[] = sprintf("%.01f", $value);
                        } else {
                                $this->values[] = $value;
                        }
                }
        }

        abstract public function getMimeType();

        abstract public function getExtension();

        public function getHeader()
        {
                return "";
        }

        public function getFooter()
        {
                return "";
        }

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
                return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<rows>\n";
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

        public function __construct($exam, $filter)
        {
                $this->board = new ScoreBoard($exam, $filter);

                $this->ldap = new LdapSearch(LdapConnection::instance());
                $this->ldap->setAttributeFilter(array("displayname", "norEduPersonNIN"));
        }

        protected function getDirectoryEntry($user, $lookup = LDAP_LOOKUP_BOTH)
        {
                if ($lookup & LDAP_LOOKUP_UID) {
                        $entry = $this->ldap->searchUID($user);
                        if ($entry->count() != 0) {
                                return $entry->first();
                        }
                }
                if ($lookup & LDAP_LOOKUP_PRINCIPAL) {
                        $entry = $this->ldap->searchPrincipalName($user);
                        if ($entry->count() != 0) {
                                return $entry->first();
                        }
                }
        }

        protected function getSocialNumber($user, $lookup = LDAP_LOOKUP_BOTH)
        {
                if (($entry = $this->getDirectoryEntry($user, $lookup))) {
                        return $entry->getNorEduPersonNIN()->first();
                }
        }

        protected function getDisplayName($user, $lookup = LDAP_LOOKUP_BOTH)
        {
                if (($entry = $this->getDirectoryEntry($user, $lookup))) {
                        return $entry->getDisplayName()->first();
                }
        }

}

// 
// Shows the score board in admin mode. This class should only be used in 
// admin pages. It is similar to ScoreBoardPrinter, but we can ignore if 
// the exam is decoded or not.
// 
class ScoreBoardAdmin extends ScoreBoardOutput
{

        private $filter;

        public function __construct($exam)
        {
                $this->filter = new ScoreBoardFilter($exam, true, true);
                parent::__construct($exam, $this->filter);
        }

        public function output()
        {
                $this->index = 0;
                $questions = $this->board->getQuestions();

                $table = new Table();

                //
                // Print headers:
                //
                $row = $table->addRow();
                $row->addHeader(_("Name"));
                $row->addHeader(_("User"));
                $row->addHeader(_("Code"));

                // 
                // Question headers:
                // 
                foreach ($questions as $question) {
                        $row->addHeader(sprintf("Q%d", ++$this->index));
                }

                //
                // Print all students:
                //
                $students = $this->board->getStudents();
                foreach ($students as $student) {
                        $row = $table->addRow();
                        $obj = $row->addData($this->getDisplayName($student->getStudentUser()));
                        $obj->setNoWrap();
                        $row->addData($student->getStudentUser());
                        $row->addData($student->getStudentCode());

                        //
                        // Print all answers for this student:
                        //
                        foreach ($questions as $question) {

                                $data = $this->board->getData($student->getStudentID(), $question->getQuestionID());

                                if (!isset($data)) {
                                        $obj = $row->addData("-");
                                        $obj->setClass("cc qu");
                                } elseif ($data->getAnswerExist() == 'N') {
                                        $obj = $row->addData("?");
                                        $obj->setClass("cc na");
                                } elseif ($data->getQuestionStatus() == 'removed') {
                                        $obj = $row->addData(sprintf("%.01f", $data->getResultScore()));
                                        $obj->setClass("cc qr");
                                } elseif ($data->hasResultScore()) {
                                        $obj = $row->addData(sprintf("%.01f", $data->getResultScore()));
                                        $obj->setClass("cc ac");
                                } elseif ($data->getAnswerExist() == 'Y') {
                                        $obj = $row->addData("X");
                                        $obj->setClass("cc nc");
                                } else {
                                        $obj = $row->addData("!");
                                        $obj->setClass("cc na");
                                }

                                if (!isset($data)) {
                                        continue;
                                }

                                if ($question->getQuestionStatus() != 'removed') {
                                        $obj->setLink(sprintf("?exam=%d&amp;answer=%d", $data->getExamID(), $data->getAnswerID()));
                                }
                        }
                }

                $table->output();
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
        private $filter;      // Score board filter

        public function __construct($exam, $writer, $format)
        {
                $this->filter = new ScoreBoardFilter($exam, true, DECODED_SHOW_UNATTENDED);
                $this->writer = $writer;
                $this->format = $format;

                parent::__construct($exam, $this->filter);
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

                if ($content) {
                        header(sprintf("Content-Type: %s;charset=utf-8", $this->format->getMimeType()));
                        header(sprintf("Content-Disposition: inline;filename=\"%s.%s\"", $this->board->getExam()->getExamName(), $this->format->getExtension()));
                        header("Cache-Control: private");
                        header("Pragma-directive: no-cache");
                        header("Cache-directive: no-cache");
                        header("Pragma: public");
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
                $grades = new ExamGrades($this->board->getExam()->getExamGrades());

                //
                // Write header list:
                //
                $i = 1;
                $array = array();
                $array[] = _("Tag");
                $array[] = _("Name");
                $array[] = _("User");
                $array[] = _("Code");
                $array[] = _("Persnr");
                foreach ($questions as $question) {
                        $array[] = sprintf("Q%d", $i++);
                }
                $array[] = _("Score");
                $array[] = _("Possible");
                $array[] = _("Max score");
                $array[] = _("Percent");
                $array[] = _("Grade");
                $this->writer->writeLine($array);

                //
                // Output the list of students.
                //
                $students = $this->board->getStudents();
                foreach ($students as $student) {
                        $array = array();
                        $student->setStudentName($this->getDisplayName($student->getStudentUser()));
                        $student->setStudentPersNr($this->getSocialNumber($student->getStudentUser()));
                        $array[] = $student->getStudentTag();
                        $array[] = $student->getStudentName();
                        $array[] = $student->getStudentUser();
                        $array[] = $student->getStudentCode();
                        $array[] = $student->getStudentPersNr();
                        foreach ($questions as $question) {
                                $data = $this->board->getData($student->getStudentID(), $question->getQuestionID());
                                if (!isset($data) || $data->getAnswerExist() == 'N') {
                                        $array[] = "";
                                } else {
                                        if ($data->hasResultScore()) {
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
                        $array[] = $grades->getGrade($score->getSum());
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
        private $ordasc;      // Order ascending.
        private $ordkey;      // Order key.

        public function __construct($exam, $filter)
        {
                parent::__construct($exam, $filter);
                $this->decoded = $this->board->getExam()->getExamDecoded() == 'Y';
                $this->verbose = false;
                $this->colorize = false;
                $this->show = new stdClass();
        }

        public function setVerbose($bool)
        {
                $this->verbose = $bool;
        }

        public function setColorized($bool)
        {
                $this->colorize = $bool;
        }

        public function setOrder($key, $asc = true)
        {
                $this->ordkey = $key;
                $this->ordasc = $asc;
        }

        public function output()
        {
                $this->index = 0;
                $questions = $this->board->getQuestions();
                $students = $this->board->getStudents();
                $grades = new ExamGrades($this->board->getExam()->getExamGrades());

                //
                // Select which field to output:
                //
                $this->show->tag = $this->decoded;
                $this->show->name = $this->decoded;
                $this->show->user = $this->decoded && $this->verbose;
                $this->show->pnr = $this->decoded && $this->verbose;
                $this->show->code = $this->verbose || !$this->decoded;
                $this->show->summary = $this->verbose;
                $this->show->percent = true;
                $this->show->avarage = true;
                $this->show->grade = true;
                $this->show->score = true;
                $this->show->links = !$this->decoded;

                $head = array();
                $data = array();

                // 
                // Aggregate headers:
                // 

                if ($this->show->tag) {
                        $head[] = array(
                                'name' => _("Tag"), 'sort' => 'tag'
                        );
                }
                if ($this->show->name) {
                        $head[] = array(
                                'name' => _("Name"), 'sort' => 'name'
                        );
                }
                if ($this->show->user) {
                        $head[] = array(
                                'name' => _("User"), 'sort' => 'user'
                        );
                }
                if ($this->show->code) {
                        $head[] = array(
                                'name' => _("Code"), 'sort' => 'code'
                        );
                }
                if ($this->show->pnr) {
                        $head[] = array(
                                'name' => _("Persnr"), 'sort' => 'persnr'
                        );
                }
                if ($this->show->score) {
                        foreach ($questions as $question) {
                                ++$this->index;
                                $head[] = $this->getHeaderData($question);
                        }
                }
                if ($this->show->summary) {
                        $head[] = array(
                                'name' => _("Summary"), 'sort' => 'summary'
                        );
                }
                if ($this->show->percent) {
                        $head[] = array(
                                'name' => _("Percent"), 'sort' => 'percent'
                        );
                }
                if ($this->show->grade) {
                        $head[] = array(
                                'name' => _("Grade"), 'sort' => 'grade'
                        );
                }

                // 
                // Aggregate data:
                // 

                foreach ($students as $student) {
                        $score = $this->board->getStudentScore($student->getStudentID());
                        $grade = $grades->getGrade($score->getSum());

                        $student->setStudentName($this->getDisplayName($student->getStudentUser()));
                        $student->setStudentSocialNumber($this->getSocialNumber($student->getStudentUser()));

                        $student->setStudentPersNr($student->getStudentSocialNumber());

                        $student->setStudentSummary(sprintf("%.01f/%.01f/%.01f", $score->getSum(), $score->getMax(), $this->board->getMaximumScore()));
                        $student->setStudentPercent(sprintf("%.01f%%", 100 * $score->getSum() / $this->board->getMaximumScore()));
                        $student->setStudentGrade($grade);
                        $student->setStudentScore($score);

                        $data[] = $student;
                }

                // 
                // Sort student data using functional object (if requested).
                // 
                if (isset($this->ordkey)) {
                        $sorter = new StudentSort($data, $this->ordkey, $this->ordasc);
                        $sorter->sort();
                        $data = $sorter->result();
                }

                // 
                // Output headers:
                // 

                $table = new Table();
                $row = $table->addRow();
                foreach ($head as $header) {
                        if (is_array($header)) {
                                $obj = $row->addHeader($header['name']);
                                $obj->setLink(sprintf("?exam=%d&action=show&verbose=%d&colorize=%d&sort=%s&desc=%d", $this->board->getExam()->getExamID(), $this->verbose, $this->colorize, $header['sort'], !$this->ordasc));
                        } else {
                                $obj = $row->addHeader($header->name);
                                if ($this->show->links && isset($header->link)) {
                                        $obj->setLink($header->link)->setTitle($header->desc);
                                } else {
                                        $obj->setAnchor($header->name, $header->desc);
                                }
                        }
                }

                // 
                // Output data:
                // 
                foreach ($data as $key => $student) {
                        $row = $table->addRow();
                        if ($this->show->tag) {
                                $row->addData($student->getStudentTag());
                        }
                        if ($this->show->name) {
                                $obj = $row->addData($student->getStudentName());
                                $obj->setNoWrap();
                                if (!$this->show->user && !$this->show->code) {
                                        $obj->setTitle(sprintf("%s: %s\n%s: %s", _("User"), $student->getStudentUser(), _("Code"), $student->getStudentCode()));
                                }
                        }
                        if ($this->show->user) {
                                $row->addData($student->getStudentUser());
                        }
                        if ($this->show->code) {
                                $obj = $row->addData($student->getStudentCode());
                                if ($this->show->links) {
                                        $obj->setLink(sprintf("?exam=%d&amp;action=correct&amp;student=%d", $student->getExamID(), $student->getStudentID()));
                                }
                        }
                        if ($this->show->pnr) {
                                $row->addData($student->getStudentSocialNumber());
                        }
                        if ($this->show->score) {
                                //
                                // Print all answers for this student:
                                //
                                foreach ($questions as $question) {
                                        $sqd = $this->board->getData($student->getStudentID(), $question->getQuestionID());
                                        if ($this->colorize) {
                                                if (!isset($sqd)) {
                                                        $obj = $row->addData("-");
                                                        $obj->setClass("cc qu");
                                                } elseif ($sqd->getAnswerExist() == 'N') {
                                                        $obj = $row->addData("?");
                                                        $obj->setClass("cc s0");
                                                } elseif ($sqd->hasResultScore()) {
                                                        $cls = $this->getColorClass($sqd->getResultScore(), $sqd->getQuestionScore());
                                                        $obj = $row->addData(sprintf("%.01f", $sqd->getResultScore()));
                                                        $obj->setClass(sprintf("cc s%d", $cls));
                                                } elseif ($sqd->getQuestionStatus() == 'removed') {
                                                        $obj = $row->addData("-");
                                                        $obj->setClass("cc qr");
                                                } elseif ($sqd->getAnswerExist() == 'Y') {
                                                        $obj = $row->addData("X");
                                                        $obj->setClass("cc nc");
                                                } else {
                                                        $obj = $row->addData("!");
                                                        $obj->setClass("cc na");
                                                }
                                        } else {
                                                if (!isset($sqd)) {
                                                        $obj = $row->addData("-");
                                                        $obj->setClass("cc qu");
                                                } elseif ($sqd->getAnswerExist() == 'N') {
                                                        $obj = $row->addData("?");
                                                        $obj->setClass("cc na");
                                                } elseif ($sqd->getQuestionStatus() == 'removed') {
                                                        $obj = $row->addData(sprintf("%.01f", $sqd->getResultScore()));
                                                        $obj->setClass("cc qr");
                                                } elseif ($sqd->hasResultScore()) {
                                                        $obj = $row->addData(sprintf("%.01f", $sqd->getResultScore()));
                                                        $obj->setClass("cc ac");
                                                } elseif ($sqd->getAnswerExist() == 'Y') {
                                                        $obj = $row->addData("X");
                                                        $obj->setClass("cc nc");
                                                } else {
                                                        $obj = $row->addData("!");
                                                        $obj->setClass("cc na");
                                                }
                                        }

                                        //
                                        // Theres no reason to continue if data is missing or if exam is decoded.
                                        //
                                        if (!isset($sqd) || $this->decoded) {
                                                continue;
                                        }

                                        if ($question->getQuestionStatus() != 'removed') {
                                                if ($question->getQuestionPublisher() == phpCAS::getUser()) {
                                                        if ($sqd->getAnswerExist() == 'Y') {
                                                                $obj->setLink(sprintf("?exam=%d&amp;action=correct&amp;answer=%d", $sqd->getExamID(), $sqd->getAnswerID()));
                                                        }
                                                } else {
                                                        if (!$this->colorize) {
                                                                $obj->setClass("cc no");
                                                        }
                                                }
                                        }
                                }
                        }
                        if ($this->show->summary ||
                            $this->show->percent ||
                            $this->show->grade) {
                                $score = $student->getStudentScore();
                                $grade = $student->getStudentGrade();
                                if ($this->show->summary) {
                                        $obj = $row->addData(sprintf("%.01f/%.01f/%.01f", $score->getSum(), $score->getMax(), $this->board->getMaximumScore()));
                                }
                                if ($this->show->percent) {
                                        $obj = $row->addData(sprintf("%.01f%%", 100 * $score->getSum() / $this->board->getMaximumScore()));
                                }
                                if ($this->show->grade) {
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
                if ($this->decoded) {
                        if ($this->show->user) {
                                $row->addData();
                        }
                        if ($this->show->code) {
                                $row->addData();
                        }
                }
                $obj = $row->addHeader(_("Avarage:"));
                $obj->setAlign(TABLE_ALIGN_RIGHT);

                foreach ($questions as $question) {
                        $score = $this->board->getQuestionScore($question->getQuestionID());
                        if ($score->getNum() == 0) {
                                $obj = $row->addData("-");
                        } else {
                                $obj = $row->addData(sprintf("%.01f", $score->getSum() / $score->getNum()));
                        }
                        if ($this->colorize) {
                                $cls = $this->getColorClass($score->getSum(), $score->getMax());
                                $obj->setClass(sprintf("cc s%d", $cls));
                        } else {
                                $obj->setClass("cc aa");
                        }
                        $obj->setTitle(sprintf("%s: %d\n%s: %.01f%%", _("Answers"), $score->getNum(), _("Percent"), $score->getPercent()));
                }

                $table->output();
        }

        //
        // Get the color class for this cell (when colorized).
        //
        private function getColorClass($val, $max)
        {
                if ($max == 0) {
                        return 0;
                }
                return 20 * floor(5 * $val / $max);
        }

        private function getHeaderData($question)
        {
                $header = new stdClass();

                if ($question->getQuestionPublisher() == phpCAS::getUser()) {
                        $header->link = sprintf("?exam=%d&amp;action=correct&amp;question=%d", $question->getExamID(), $question->getQuestionID());
                }

                if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        $question->setQuestionBody(strip_tags($question->getQuestionText()));
                } elseif (
                    $question->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE ||
                    $question->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE) {
                        $options = Exam::getQuestionChoice($question->getQuestionText());
                        $question->setQuestionBody(strip_tags($options[0]));
                } elseif (!$question->hasQuestionBody()) {
                        $question->setQuestionBody(""); // Fix undef warning
                }

                $header->name = sprintf("Q%d", $this->index);
                $header->desc = sprintf("%s %s\n\n%s\n\n%s: %.01f\n%s: %s", _("Question"), $question->getQuestionName(), $question->getQuestionBody(), _("Max score"), $question->getQuestionScore(), _("Publisher"), $this->getDisplayName($question->getQuestionPublisher()));
                if ($question->getQuestionStatus() == 'removed') {
                        if ($question->getQuestionComment() != null) {
                                $header->desc .= sprintf("\n\n%s: %s", _("This question has been removed"), $question->getQuestionComment());
                        } else {
                                $header->desc .= sprintf("\n\n%s.", _("This question has been removed"));
                        }
                }
                return $header;
        }

}

// 
// Sort student data in array.
// 
class StudentSort
{

        private $data;
        private $ordkey;
        private $ordasc;
        private static $self;

        public function __construct($data, $ordkey, $ordasc)
        {
                $this->data = $data;
                $this->ordkey = $ordkey;
                $this->ordasc = $ordasc;
        }

        public function sort()
        {
                self::$self = $this;
                usort($this->data, 'StudentSort::dispatch');
                self::$self = null;
        }

        public function result()
        {
                return $this->data;
        }

        public function dispatch($a, $b)
        {
                return self::$self->compare($a, $b);
        }

        private function compare($a, $b)
        {
                $func = sprintf("getStudent%s", ucfirst($this->ordkey));

                if ($this->ordasc) {
                        return strcmp($a->$func(), $b->$func());
                } else {
                        return strcmp($b->$func(), $a->$func());
                }
        }

}

?>
