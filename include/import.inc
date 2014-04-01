<?php

//
// Copyright (C) 2010, 2014 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/import.inc
// Author: Anders Lövgren
// Date:   2010-12-09
//
// Import utility. This files defines classes for importing both native XML
// formats (OpenExam projects) and foreign data (i.e. Excel files containing
// question banks or students).
// 
// Classes should implement the Import interface
// 
// Include data options:
// 
if (!defined("OPENEXAM_IMPORT_INCLUDE_PROJECT")) {
        define("OPENEXAM_IMPORT_INCLUDE_PROJECT", 1);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_TOPICS")) {
        define("OPENEXAM_IMPORT_INCLUDE_TOPICS", 2);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_QUESTIONS")) {
        define("OPENEXAM_IMPORT_INCLUDE_QUESTIONS", 4);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_ROLES")) {
        define("OPENEXAM_IMPORT_INCLUDE_ROLES", 8);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_ANSWERS")) {
        define("OPENEXAM_IMPORT_INCLUDE_ANSWERS", 16);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_STUDENTS")) {
        define("OPENEXAM_IMPORT_INCLUDE_STUDENTS", 32);
}
// 
// Simplified include options:
// 
if (!defined("OPENEXAM_IMPORT_INCLUDE_DEFAULT")) {
        define("OPENEXAM_IMPORT_INCLUDE_DEFAULT", OPENEXAM_IMPORT_INCLUDE_PROJECT | \
            OPENEXAM_IMPORT_INCLUDE_TOPICS | OPENEXAM_IMPORT_INCLUDE_QUESTIONS);
}
if (!defined("OPENEXAM_IMPORT_INCLUDE_ALL")) {
        define("OPENEXAM_IMPORT_INCLUDE_ALL", OPENEXAM_IMPORT_INCLUDE_DEFAULT | \
            OPENEXAM_IMPORT_INCLUDE_ROLES | OPENEXAM_IMPORT_INCLUDE_ANSWERS | \
            OPENEXAM_IMPORT_INCLUDE_STUDENTS);
}

// 
// Supported versions of native export formats:
// 
if (!defined("OPENEXAM_IMPORT_FORMAT_VERSION")) {
        define("OPENEXAM_IMPORT_FORMAT_VERSION", 6072);
}

class ImportException extends Exception
{

        public function __construct($message, $code = 0, $previous = null)
        {
                parent::__construct($message, $code, $previous);
        }

}

//
// Include PHP-ExcelReader files:
//
require_once('include/import/excel/reader.php');

/**
 * The interface for concrete import classes. 
 */
interface Import
{

        /**
         * Prototype the constructor:
         */
        function __construct($name, $file, $mime, $size, $accept = "");

        /**
         * Prepare import.
         * 
         * This function is called by the import service consumer before 
         * calling read() to start the actual import. Override in child class
         * if house keeping need to be done.
         */
        function open();

        /**
         * Parse current opened import file and save data to exam.
         * 
         * If exam is exam == 0, then a new exam is created, otherwise the
         * selected exam is updated. This is not supported by all import
         * targets (currently only openexam projects).
         * 
         * @param int $exam The exam ID.
         * @param Database $db The database connection.
         * @return int The exam ID.
         */
        function read($exam, &$db, $what);

        /**
         * Finish import.
         * 
         * This function is called by the import service consumer when
         * read() has finixhed the import. Override in child class if house
         * keeping need to be done.
         */
        function close();
}

/**
 * The import data.
 * 
 * All import classes should use this class to define the data to be inserted
 * in the database. For creating an instance of this class and load an
 * XML document, do either one of these:
 * <code>
 * // Load from file:
 * $data = simplexml_load_file($xmlfile, 'ImportData');
 * 
 * // Load from string:
 * $data = simplexml_load_string($xmldoc, 'ImportData');
 * 
 * // Pass string direct to base class constructor:
 * $data = new ImportData('&lt;root/&gt;');
 * </code>
 * 
 * This really shows how sucky OOP in PHP is. It's not possible to define the
 * map as property as it interferes with SimpleXMLElement.
 * 
 * @see docs/openexam.xsd
 */
class ImportData extends SimpleXMLElement
{

        private static $mmap;   // Must be static

        public function map()
        {
                if (!isset(self::$mmap)) {
                        self::$mmap = new stdClass();
                }
                return self::$mmap;
        }

}

/**
 * Insert import data.
 */
class ImportInsert
{

        protected $exam;
        protected $db;

        /**
         * Constructor.
         * @param int $exam The exam ID.
         * @param Database $db The database connection.
         */
        public function __construct($exam, &$db)
        {
                $this->exam = $exam;
                $this->db = $db;
        }

        public function getExamID()
        {
                return $this->exam;
        }

        /**
         * Insert import data.
         * @param ImportData $data The data to import.
         * @param int $what What sections to import.
         */
        public function insert($data, $what = OPENEXAM_IMPORT_INCLUDE_ALL)
        {
                $db = Database::getConnection();
                if ($db->supports('transaction')) {
                        $db->beginTransaction();
                }

                if ($what & OPENEXAM_IMPORT_INCLUDE_PROJECT) {
                        $this->insertProjectData($data);
                }
                if ($what & OPENEXAM_IMPORT_INCLUDE_ROLES) {
                        $this->insertRoles($data);
                }
                if ($what & OPENEXAM_IMPORT_INCLUDE_TOPICS) {
                        $this->insertTopics($data);
                }
                if ($what & OPENEXAM_IMPORT_INCLUDE_QUESTIONS) {
                        $this->insertQuestions($data);
                }
                if ($what & OPENEXAM_IMPORT_INCLUDE_STUDENTS) {
                        $this->insertStudents($data);
                }
                if ($what & OPENEXAM_IMPORT_INCLUDE_ANSWERS) {
                        $this->insertAnswers($data);
                }

                if ($db->in_transaction) {
                        $db->commit();
                }
        }

        private function insertProjectData(&$data)
        {
                $data->project->name = sprintf("%s (Imported %s)", $data->project->name, strftime("%x %X"));
                //
                // Set project properties:
                //
                if ($this->exam != 0) {
                        $sql = sprintf("UPDATE  exams SET
                                                name = '%s',
                                                description = '%s',
                                                orgunit = '%s',
                                                starttime = '%s',
                                                endtime = '%s',
                                                created = '%s',
                                                grades = '%s'
                                        WHERE   id = %d", $data->project->name, $data->project->description, $data->project->orgunit, $data->project->starttime, $data->project->endtime, $data->project->created, $data->project->grades, $this->exam);
                        $this->insertData($sql);
                } else {
                        $sql = sprintf("INSERT INTO exams(name, descr, orgunit, starttime, endtime, created, creator, grades)
                                                        VALUES('%s','%s','%s','%s','%s','%s','%s','%s')", $data->project->name, $data->project->description, $data->project->orgunit, $data->project->starttime, $data->project->endtime, $data->project->created, phpCAS::getUser(), $data->project->grades);
                        $this->exam = $this->insertData($sql, "exams");
                }

                // 
                // Need to fix past start time.
                // 
                if (strtotime($data->project->starttime) < time()) {
                        $sql = sprintf("UPDATE  exams SET 
                                                starttime = '%s', 
                                                endtime = '%s' 
                                        WHERE id = %d", DATETIME_NONE, DATETIME_NONE, $this->exam);
                        $this->insertData($sql);
                }
        }

        private function insertRoles(&$data)
        {
                $roles = array(
                        'contributor' => 'contributors',
                        'examinator'  => 'examinators',
                        'decoder'     => 'decoders'
                );
                foreach ($roles as $name => $table) {
                        foreach ($data->roles->$name as $role) {
                                print_r($role->user);
                                foreach ($role->user as $user) {
                                        $sql = sprintf("INSERT INTO %s(exam_id, user)
                                                        VALUES(%d, '%s')", $table, $this->exam, $user);
                                        $this->insertData($sql);
                                }
                        }
                }
        }

        private function insertTopics(&$data)
        {
                $data->map()->topics = array();

                foreach ($data->topics->topic as $topic) {
                        $sql = sprintf("INSERT INTO topics(exam_id, name, randomize)
                                        VALUES(%d,'%s','%s')", $this->exam, $topic->name, $topic->randomize);
                        $data->map()->topics[(int) $topic['id']] = $this->insertData($sql, "topics");
                }
        }

        private function createTopics(&$data)
        {
                $tnode = $data->addChild("topics");
                foreach ($data->questions->question as $question) {
                        foreach ($data->topics->topic as $topic) {
                                if ($topic->id == $question->topic) {
                                        continue;
                                }
                        }
                        $child = $tnode->addChild("topic");
                        $child->addAttribute("id", $question->topic);
                        $child->addChild("name", sprintf("topic_%d", $question->topic));
                        $child->addChild("random", 0);
                }
                $this->insertTopics($data);
        }

        private function insertQuestions(&$data)
        {
                $data->map()->questions = array();

                if (!isset($data->topics) || !isset($data->map()->topics)) {
                        $this->createTopics($data);
                }

                foreach ($data->questions->question as $question) {
                        $question->quest = $this->db->escape($question->text);
                        $sql = sprintf("INSERT INTO questions(exam_id, topic_id, score, name, quest, user, video, image, audio, type, status, comment)
                                        VALUES(%d,%d,%F,'%s','%s','%s','%s','%s','%s','%s','%s','%s')", $this->exam, $data->map()->topics[(int) $question['topic']], $question->score, $question->name, $question->quest, $question->publisher, $question->video, $question->image, $question->audio, $question->type, $question->status, $question->comment);
                        $data->map()->questions[(int) $question['id']] = $this->insertData($sql, "questions");
                }
        }

        private function insertStudents(&$data)
        {
                $data->map()->students = array();

                foreach ($data->students->student as $student) {
                        $sql = sprintf("INSERT INTO students(exam_id, user, code)
                                        VALUES(%d,'%s','%s')", $this->exam, $student->user, $student->code);
                        $data->map()->students[(string) $student->user] = $this->insertData($sql, "students");
                }
        }

        private function insertAnswers(&$data)
        {
                $data->map()->answers = array();

                if (!isset($data->questions)) {
                        throw new ImportException("Can't import answers without questions.");
                }
                if (!isset($data->students)) {
                        throw new ImportException("Can't import answers without students.");
                }

                foreach ($data->answers->answer as $answer) {
                        $answer->answer = $this->db->escape($answer->text);
                        $sql = sprintf("INSERT INTO answers(question_id, student_id, answered, answer, comment)
                                        VALUES(%d,%d,'Y','%s','%s')", $data->map()->questions[(int) $answer['question']], $data->map()->students[(string) $answer['user']], $answer->answer, $answer->acomment);
                        $data->map()->answers[(int) $answer['id']] = $this->insertData($sql, "answers");
                }
        }

        private function insertData($sql, $table = null)
        {
                printf(__METHOD__ . " SQL: %s'\n", $sql);
                $res = $this->db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                if (isset($table)) {
                        return $this->db->lastInsertID($table);
                }
        }

}

/**
 * The base class for all importers.
 */
abstract class ImportBase implements Import
{

        protected $name;
        protected $file;
        protected $mime;
        protected $size;
        protected $user;
        protected $data;      // Import data object.

        /**
         * Constructor.
         * @param string $name Name of uploaded file-
         * @param string $file Path to uploaded file.
         * @param string $mime MIME-type of uploaded file.
         * @param int $size Size of uploaded file.
         * @param string|array $accept Accepted MIME-types.
         * @throws ImportException
         */

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                $accepted = false;
                $expected = is_array($accept) ? implode("|", $accept) : $accept;

                if (!isset($mime)) {
                        $accepted = true;       // Give it a try
                } else {
                        if (is_array($accept)) {
                                foreach ($accept as $type) {
                                        if ($type == $mime) {
                                                $accepted = true;
                                                break;
                                        }
                                }
                        } else {
                                if ($mime == $accept) {
                                        $accepted = true;
                                }
                        }
                }

                if (!$accepted) {
                        throw new ImportException(sprintf(_("Wrong MIME type (%s) on uploaded file %s (expected %s)"), $mime, $name, $expected));
                }
                if ($size == 0) {
                        throw new ImportException(sprintf(_("Empty file %s uploaded"), $name));
                }

                $this->name = $name;
                $this->file = $file;
                $this->mime = $mime;
                $this->size = $size;

                $this->user = phpCAS::getUser();
        }

        /**
         * Cleanup function.
         * 
         * Remove HTML tags and other junk from the input strings. This function
         * should be called on for any text field read from the excel-file.
         * 
         * @param string $str The input string.
         * @return string
         */
        protected static function cleanup($str)
        {
                $find = array("<br/><br/>", " ?", "<br/>", "  ", "\n\n\n");
                $repl = array("\n\n", "?", " ", " ", "\n\n");

                $str = preg_replace("|<p>(.*?)</p>|", "$1\n\n", $str);
                $str = str_replace($find, $repl, $str);

                return utf8_encode(htmlentities(html_entity_decode(trim($str))));
        }

        public function open()
        {
                // Dummy
        }

        public function close()
        {
                // Dummy
        }

}

class ImportOpenExam extends ImportBase
{

        private static $supported = array(6071, 6072);
        private static $accept = array("application/xml", "text/xml");

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ImportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ImportException("The DOM Document extension (dom) is not loaded");
                }
                parent::__construct($name, $file, $mime, $size, self::$accept);
        }

        public function open()
        {
                $this->data = simplexml_load_file($this->file, 'ImportData');
                $this->verify();
        }

        //
        // Verify that loaded document can be imported.
        //
        private function verify()
        {
                if (!isset($this->data['format'])) {
                        $message = _("Missing XML format attribute. This don't look like an OpenExam project data file.");
                        throw new ImportException($message);
                }
                if (!in_array($this->data['format'], self::$supported)) {
                        $message = sprintf(_("Unsupported XML format version: %d. This file can not be imported."), $this->data['@attributes']['format']);
                        throw new ImportException($message);
                }
        }

        function read($exam, &$db, $what)
        {
                $inserter = new ImportInsert($exam, $db);
                $inserter->insert($this->data, $what);
                return $inserter->getExamID();
        }

}

/**
 * Base class for import from Ping-Pong.
 */
abstract class ImportPingPong extends ImportBase
{

        const format = "QI625";
        const expect = "Exported from the questionbank in PING PONG";
        const xmldoc1 = '<?xml version="1.0" encoding="utf-8"?>\n<questions></questions>\n';
        const xmldoc = '<openexam/>';

        private $questions = array();
        private $question = null;
        private $category = null;

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                parent::__construct($name, $file, $mime, $size, $accept);
                $this->data = new ImportData(self::xmldoc);
        }

        protected function append($key, $val)
        {
                printf("(key, val) = (%s, %s)\n", $key, $val);
                if ($key == "Question") {
                        if (isset($this->question)) {
                                // 
                                // Map multiple choice with a single alternative to freetext.
                                // 
                                if (isset($this->question['choice']) && count($this->question['choice']) <= 1) {
                                        $this->question['type'] = "freetext";
                                        unset($this->question['choice']);
                                }

                                $this->questions[$this->category][] = $this->question;
                        }
                        $this->question = array("comment" => "", "score" => 1.0, "user" => $this->user);
                }
                if ($key == "Category") {
                        $this->category = self::cleanup($val);
                }
                if ($key == "Name") {
                        $this->question['name'] = self::cleanup($val);
                }
                if ($key == "Description") {
                        $this->question['comment'] = self::cleanup($val);
                }
                if ($key == "Text") {
                        $this->question['body'] = self::cleanup($val);
                }
                if ($key == "Max points") {
                        $this->question['score'] = $val;
                }
                if ($key == "Type") {
                        if ($val == "Multiple choice") {
                                $this->question['type'] = "multiple";
                                $this->question['choice'] = array();
                        } elseif ($val == "Single choice") {
                                $this->question['type'] = "single";
                                $this->question['choice'] = array();
                        } elseif ($val == "Free writing") {
                                $this->question['type'] = "freetext";
                        }
                }
                if ($key == "Correct") {
                        $this->question['choice'][self::cleanup($val)] = true;
                }
                if ($key == "Incorrect") {
                        $this->question['choice'][self::cleanup($val)] = false;
                }
                if ($key == "Marking guide") {   // TOOD: what to do with this?
                        $this->question['guide'] = $val;
                }
        }

        // 
        // Prepare for insert.
        // 
        private function prepare(&$question, &$db)
        {
                $question['select'] = 0;
                if (isset($question['choice'])) {
                        foreach ($question['choice'] as $boolean) {
                                if ($boolean == true) {
                                        $question['select'] ++;
                                }
                        }
                }
                if ($question['select'] > 1) {
                        $question['type'] = 'multiple';
                } elseif ($question['select'] == 1) {
                        $question['type'] = 'single';
                } else {
                        $question['type'] = 'freetext';
                }
                if ($question['type'] == 'multiple' || $question['type'] == 'single') {
                        $question['quest'] = sprintf("%s\n\n%s", $question['body'], json_encode($question['choice']));
                        $question['quest'] = $db->escape($question['quest']);
                } else {
                        $question['quest'] = $db->escape($question['body']);
                }
        }

        public function read($exam, &$db, $what)
        {
                $tnode = $this->data->addChild("topics");
                $qnode = $this->data->addChild("questions");

                $tindex = 0;
                $qindex = 0;

                foreach ($this->questions as $category => $questions) {
                        $child = $tnode->addChild("topic");
                        $child->addAttribute("id", ++$tindex);
                        $child->addChild("name", $category);
                        $child->addChild("random", 0);
                        foreach ($questions as $question) {
                                $this->prepare($question, $db);
                                $child = $qnode->addChild("question");
                                $child->addAttribute("id", ++$qindex);
                                $child->addAttribute("topic", $tindex);
                                $child->addChild("score", $question['score']);
                                $child->addChild("name", $question['name']);
                                $child->addChild("text", $question['quest']);
                                $child->addChild("publisher", $question['user']);
                                $child->addChild("type", $question['type']);
                                $child->addChild("comment", $question['comment']);
                        }
                }

                $inserter = new ImportInsert($exam, $db);
                $inserter->insert($this->data, $what);

                return $inserter->getExamID();
        }

}

class ImportPingPongExcel extends ImportPingPong
{

        private static $accept = array("application/vnd.ms-excel", "application/vnd.ms-office");
        private $doc;

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                parent::__construct($name, $file, $mime, $size, self::$accept);
        }

        public function open()
        {
                $this->doc = new Spreadsheet_Excel_Reader();
        }

        public function read($exam, &$db, $what)
        {
                $this->doc->read($this->file);

                $sheet = new stdClass();
                $sheet->rows = $this->doc->sheets[0]['numRows'];
                $sheet->cols = $this->doc->sheets[0]['numCols'];
                $sheet->cell = $this->doc->sheets[0]['cells'];

                if ($sheet->cell[1][1] != self::expect) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::expect);
                        throw new ImportException($message);
                }
                if ($sheet->cell[2][2] != self::format) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::format);
                        throw new ImportException($message);
                }

                for ($i = 1; $i <= $sheet->rows; $i++) {
                        if (!isset($sheet->cell[$i])) {
                                continue;
                        } elseif (!isset($sheet->cell[$i][2])) {
                                $sheet->cell[$i][2] = "";
                        } else {
                                parent::append($sheet->cell[$i][1], $sheet->cell[$i][2]);
                        }
                }

                return parent::read($exam, $db, $what);
        }

}

class ImportPingPongText extends ImportPingPong
{

        const accept = "text/plain";
        const delimiter = "\t";

        private $stream;

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                parent::__construct($name, $file, $mime, $size, self::accept);
        }

        public function open()
        {
                $this->stream = fopen($this->file, "r");
        }

        private function next()
        {
                if (!($str = fgets($this->stream))) {
                        return null;
                } else {
                        return explode(self::delimiter, $str);
                }
        }

        public function read($exam, &$db, $what)
        {
                if (($data = $this->next()) && $data[0] != self::expect) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::expect);
                        throw new ImportException($message);
                }

                if (($data = $this->next()) && $data[1] != self::format) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::format);
                        throw new ImportException($message);
                }

                while ($data = $this->next()) {
                        if (count($data) == 0) {
                                continue;
                        } else {
                                parent::append($data[0], $data[1]);
                        }
                }

                return parent::read($exam, $db, $what);
        }

        public function close()
        {
                fclose($this->stream);
        }

}

class FileImport
{

        const octet_stream = "application/octet-stream";

        // 
        // Get MIME type of file using the PECL extension fileinfo. Throws
        // an ImportException if extension is not loaded.
        // 
        public static function getMimeType($file)
        {
                if (!extension_loaded("fileinfo")) {
                        throw new ImportException("The PECL extension fileinfo is not loaded.");
                }

                if (!($res = finfo_open(FILEINFO_MIME_TYPE))) {
                        throw new ImportException("Failed open fileinfo database.");
                }
                if (!($mime = finfo_file($res, $file))) {
                        throw new ImportException(sprintf("Failed get MIME type of %s.", $file));
                }
                if (!finfo_close($res)) {
                        throw new ImportException("Failed close fileinfo database.");
                }

                return $mime;
        }

        //
        // Factory function returning an importer. These are the parameters:
        //
        //   type: Type of file to import (pp or oe)
        //   name: The name of the uploaded file.
        //   file: An absolute path to the uploaded file.
        //   mime: The MIME type.
        //   size: Uloaded file size
        //
        // Description of type argument:
        //
        //   pp - ping pong
        //   oq - open exam exported questions
        //   op - open exam complete project
        //   sr - student registrations
        //
        public static function getReader($type, $name, $file, $mime, $size)
        {
                $extension = substr(strrchr($name, "."), 1);

                if (!is_uploaded_file($file)) {
                        throw new ImportException(sprintf(_("Possible file upload attack: %s"), $file));
                }
                if ($mime == self::octet_stream) {
                        $mime = self::getMimeType($file);
                }

                if ($type == "pp") {
                        switch ($extension) {
                                case "xls":
                                        return new ImportPingPongExcel($name, $file, $mime, $size);
                                        break;
                                case "tab":
                                case "txt":
                                        return new ImportPingPongText($name, $file, $mime, $size);
                                        break;
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files. Please select an file containing a Ping-Ping question bank."), $extension));
                                        break;
                        }
                } elseif ($type == "oq" || $type == "op") {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExam($name, $file, $mime, $size);
                                        break;
                        }
                } elseif ($type == "sr") {       // student registrations
                        switch ($extension) {
                                case "xlsx":
                                        return new ImportStudentsExcel2007($name, $file, $mime, $size);
                                        break;
                                case "xls":
                                        return new ImportStudentsExcel2003($name, $file, $mime, $size);
                                        break;
                                case "tab":
                                case "txt":
                                        return new ImportStudentsTextTab($name, $file, $mime, $size);
                                        break;
                                case "csv":
                                        return new ImportStudentsTextCsv($name, $file, $mime, $size);
                                        break;
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files. Please select an file containing a Ping-Ping question bank."), $extension));
                                        break;
                        }
                }
        }

}

?>
