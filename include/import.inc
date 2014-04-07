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

//
// Include PHP-ExcelReader files:
//
require_once('include/import/excel/reader.php');
require_once('PHPExcel.php');
require_once('PHPExcel/IOFactory.php');

class ImportException extends Exception
{

        public function __construct($message, $code = 0, $previous = null)
        {
                parent::__construct($message, $code, $previous);
        }

}

/**
 * The interface for concrete import classes. 
 */
interface Import
{

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
         * @param int $what Bitmask defining sections to parse. 
         */
        function read();

        /**
         * Delegate insert to inserter object.
         * @param ImportInsert $inserter The inserter object.
         * @param int $what Bitmask defining sections to insert. 
         * @return int The exam ID.
         */
        function insert($inserter);

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
         * @param int $filter Which sections to import.
         */
        public function insert($data, $filter = OPENEXAM_IMPORT_INCLUDE_ALL)
        {
                $db = Database::getConnection();
                if ($db->supports('transaction')) {
                        $db->beginTransaction();
                }

                if (!isset($data->questions) || !isset($data->students)) {
                        $filter &= ~OPENEXAM_IMPORT_INCLUDE_ANSWERS;
                }

                if ($filter & OPENEXAM_IMPORT_INCLUDE_PROJECT) {
                        $this->insertProjectData($data);
                }
                if ($filter & OPENEXAM_IMPORT_INCLUDE_ROLES) {
                        $this->insertRoles($data);
                }
                if ($filter & OPENEXAM_IMPORT_INCLUDE_TOPICS) {
                        $this->insertTopics($data);
                }
                if ($filter & OPENEXAM_IMPORT_INCLUDE_QUESTIONS) {
                        $this->insertQuestions($data);
                }
                if ($filter & OPENEXAM_IMPORT_INCLUDE_STUDENTS) {
                        $this->insertStudents($data);
                }
                if ($filter & OPENEXAM_IMPORT_INCLUDE_ANSWERS) {
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

                if (isset($data->roles)) {
                        foreach ($roles as $name => $table) {
                                if (isset($data->roles->$name)) {
                                        foreach ($data->roles->$name as $role) {
                                                foreach ($role->user as $user) {
                                                        $sql = sprintf("INSERT INTO %s(exam_id, user)
                                                        VALUES(%d, '%s')", $table, $this->exam, $user);
                                                        $this->insertData($sql);
                                                }
                                        }
                                }
                        }
                }
        }

        private function insertTopics(&$data)
        {
                $data->map()->topics = array();

                if (isset($data->topics)) {
                        foreach ($data->topics->topic as $topic) {
                                $sql = sprintf("INSERT INTO topics(exam_id, name, randomize)
                                        VALUES(%d,'%s','%s')", $this->exam, $topic->name, $topic->randomize);
                                $data->map()->topics[(int) $topic['id']] = $this->insertData($sql, "topics");
                        }
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

                if (isset($data->questions)) {
                        foreach ($data->questions->question as $question) {
                                $question->quest = $this->db->escape($question->text);
                                $sql = sprintf("INSERT INTO questions(exam_id, topic_id, score, name, quest, user, video, image, audio, type, status, comment)
                                        VALUES(%d,%d,%F,'%s','%s','%s','%s','%s','%s','%s','%s','%s')", $this->exam, $data->map()->topics[(int) $question['topic']], $question->score, $question->name, $question->quest, $question->publisher, $question->video, $question->image, $question->audio, $question->type, $question->status, $question->comment);
                                $data->map()->questions[(int) $question['id']] = $this->insertData($sql, "questions");
                        }
                }
        }

        private function insertStudents(&$data)
        {
                $data->map()->students = array();

                if (isset($data->students)) {
                        foreach ($data->students->student as $student) {
                                $sql = sprintf("INSERT INTO students(exam_id, user, code, tag)
                                        VALUES(%d,'%s','%s','%s')", $this->exam, $student->user, $student->code, $student->tag);
                                $data->map()->students[(string) $student->user] = $this->insertData($sql, "students");
                        }
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

                if (isset($data->answers)) {
                        foreach ($data->answers->answer as $answer) {
                                $answer->answer = $this->db->escape($answer->text);
                                $sql = sprintf("INSERT INTO answers(question_id, student_id, answered, answer, comment)
                                        VALUES(%d,%d,'Y','%s','%s')", $data->map()->questions[(int) $answer['question']], $data->map()->students[(string) $answer['user']], $answer->answer, $answer->acomment);
                                $data->map()->answers[(int) $answer['id']] = $this->insertData($sql, "answers");
                        }
                }
        }

        private function insertData($sql, $table = null)
        {
                // printf(__METHOD__ . " SQL: %s'\n", $sql);
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

        protected $filter;
        protected $accept;
        protected $name;
        protected $file;
        protected $mime;
        protected $size;
        protected $user;
        protected $data;

        /**
         * Constructor.
         * @param string|array $accept Accepted MIME-types.
         * @param int $filter The sections to import.
         */
        public function __construct($accept, $filter = OPENEXAM_IMPORT_INCLUDE_ALL)
        {
                $this->accept = $accept;
                $this->filter = $filter;
        }

        /**
         * Set file to import.
         * 
         * This function performs sanity check on the submitted MIME type
         * and throws an ImportException if the MIME-type don't match the 
         * expected.
         * 
         * @param string $name Name of uploaded file.
         * @param string $file Path to uploaded file.
         * @param string $mime MIME-type of uploaded file.
         * @param int $size Size of uploaded file.
         * @throws ImportException
         */
        public function setFile($name, $file, $mime, $size)
        {
                if (!is_uploaded_file($file)) {
                        throw new ImportException(sprintf(_("Possible file upload attack: %s"), $file));
                }
                if ($mime == FileImport::octet_stream) {
                        $mime = FileImport::getMimeType($file);
                }

                $accepted = false;
                $expected = is_array($this->accept) ? implode("|", $this->accept) : $this->accept;

                if (!isset($mime)) {
                        $accepted = true;       // Give it a try
                } else {
                        if (is_array($this->accept)) {
                                foreach ($this->accept as $type) {
                                        if ($type == $mime) {
                                                $accepted = true;
                                                break;
                                        }
                                }
                        } else {
                                if ($mime == $this->accept) {
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

        public function setFilter($filter)
        {
                $this->filter = $filter;
        }

        public function getFilter()
        {
                return $this->filter;
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
                // Ignore
        }

        public function read()
        {
                // Ignore
        }

        public function insert($inserter)
        {
                $inserter->insert($this->data, $this->filter);
                return $inserter->getExamID();
        }

        public function close()
        {
                // Ignore
        }

}

class ImportOpenExam extends ImportBase
{

        private static $supported = array(6071, 6072);
        private static $mimedef = array("application/xml", "text/xml");

        public function __construct($accept = "")
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ImportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ImportException("The DOM Document extension (dom) is not loaded");
                }
                parent::__construct(self::$mimedef);
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

}

/**
 * Base class for import from Ping-Pong.
 */
abstract class ImportPingPong extends ImportBase
{

        const format = "QI625";
        const expect = "Exported from the questionbank in PING PONG";
        const xmldoc = '<openexam/>';

        private $questions = array();
        private $question = null;
        private $category = null;

        public function __construct($accept = "")
        {
                parent::__construct($accept);
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

        public function read()
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
        }

}

class ImportPingPongExcel extends ImportPingPong
{

        private static $mimedef = array("application/vnd.ms-excel", "application/vnd.ms-office");
        private $reader;

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new Spreadsheet_Excel_Reader();
        }

        public function read()
        {
                $this->reader->read($this->file);

                $sheet = new stdClass();
                $sheet->rows = $this->reader->sheets[0]['numRows'];
                $sheet->cols = $this->reader->sheets[0]['numCols'];
                $sheet->cell = $this->reader->sheets[0]['cells'];

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

                parent::read();
        }

}

class ImportPingPongText extends ImportPingPong
{

        const accept = "text/plain";
        const delimiter = "\t";

        private $stream;

        public function __construct($accept = "")
        {
                parent::__construct(self::accept);
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

        public function read()
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

                parent::read();
        }

        public function close()
        {
                fclose($this->stream);
        }

}

/**
 * Base class for student import classes.
 * 
 * The $students member contains an associative array whose key are the
 * user ID and the value are the optional assigned tag.
 */
class ImportStudents extends ImportBase
{

        const xmldoc = '<openexam/>';
        // Constant for setMapping():
        const tag = 'tag';
        const user = 'user';
        const code = 'code';
        const pnr = 'pnr';
        const row = 'row';

        private static $pnrhpatt = "/^personnummer|pers.?nr|p.?nr|pnum$/";
        private static $pnrvpatt = "/^\d{6,8}-?\d{4}|^\d{6,8}-?\d{3}[a-zA-Z]$/";
        protected $students = array();
        protected $opts;
        protected $reader;
        private $excel;         // the excel object
        private $sheet;         // active sheet
        private $cols;          // columns in active sheet
        private $rows;          // rows in active sheet
        private $sdat;          // php array (0-based) of sheet data
        private $first = 0;     // first row

        public function __construct($accept)
        {
                parent::__construct($accept);
                $this->data = new ImportData(self::xmldoc);
                $this->opts = new stdClass();
        }

        /**
         * Set tagging.
         * 
         * A numeric value defines the column containing some generic string
         * to be associated with the added student. It the tag value is a
         * string, then it applies to all imported accounts.
         * 
         * @param int|string $tag The column or tag string.
         */
        public function setTagging($tag)
        {
                if (is_numeric($tag)) {
                        $this->opts->coltag = $tag;
                } elseif (is_string($tag) && strlen($tag) == 1) {
                        $this->opts->coltag = $tag - ord('A');
                } else {
                        $this->opts->tagstr = $tag;
                }
        }

        /**
         * Set column mapping.
         * 
         * Possible values for the $type aregument are:
         * <ul>
         * <li>'tag'    -> Define the tag column.</li>
         * <li>'user'   -> Define the account column.</li>
         * <li>'code'   -> Define the anonymous code column.</li>
         * <li>'persnr' -> Define the person number column.</li>
         * </ul>
         * 
         * @param string $type The type identifier.
         * @param int $index The column index.
         */
        public function setMapping($type, $index)
        {
                switch ($type) {
                        case self::tag:
                                $this->opts->coltag = $index;
                                break;
                        case self::user:
                                $this->opts->coluser = $index;
                                break;
                        case self::pnr:
                                $this->opts->colpnr = $index;
                                break;
                        case self::code:
                                $this->opts->colcode = $index;
                }
        }

        /**
         * Set first row to import from.
         * @param int $row The row number.
         */
        public function setStartRow($row)
        {
                $this->first = $row;
        }

        public function read()
        {
                $this->excel = $this->reader->load($this->file);
                $this->sheet = $this->excel->setActiveSheetIndex(0);

                $this->cols = ord($this->sheet->getHighestColumn()) - ord('A');
                $this->rows = $this->sheet->getHighestRow();
                $this->sdat = $this->sheet->toArray();

                if (isset($this->opts->coluser)) {
                        $this->readAccounts($this->opts->coluser);
                } elseif (isset($this->opts->colpnr)) {
                        $this->readPersNr($this->opts->colpnr);
                        $this->lookupPersNr();
                } else {
                        $this->readDetect();    // try to detect pers.nr.
                        $this->lookupPersNr();
                }

                if (count($this->students) == 0) {
                        throw new ImportException(_("No account information in import file."));
                }

                if (isset($this->opts->coltag)) {
                        $this->readTag($this->opts->coltag);
                }
                if (isset($this->opts->tagstr)) {
                        $this->assignTag($this->opts->tagstr);
                }
                if (isset($this->opts->colcode)) {
                        $this->readCode($this->opts->colcode);
                }

                $pnode = $this->data->addChild('students');
                foreach ($this->students as $user => $val) {
                        $snode = $pnode->addChild('student');
                        $snode->addChild('user', $user);
                        $snode->addChild('code', $val[self::code]);
                        $snode->addChild('tag', $val[self::tag]);
                }
        }

        // 
        // Read accounts from named column.
        // 
        private function readAccounts($column)
        {
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        $value = $this->sdat[$r][$column];
                        $this->setValue(null, self::user, $value);
                        $this->setValue($value, self::row, $r);
                }
        }

        // 
        // Read personal numbers from named column.
        // 
        private function readPersNr($column)
        {
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        $value = $this->sdat[$r][$column];
                        if (preg_match(self::$pnrvpatt, $value)) {
                                $this->setValue(null, self::user, $value);
                                $this->setValue($value, self::row, $r);
                        } elseif ($r != $this->first) {
                                throw new ImportException(sprintf("Unmatched personal number in cell '%d,%d'", $r, $column));
                        }
                }
        }

        // 
        // Try to detect the column containing the personal number. If it
        // fails, scan each row trying to match a cell with a personal number.
        // 
        private function readDetect()
        {
                // 
                // Try to detect an header matching one of the hpattern.
                // 
                for ($c = 0; $c < $this->cols; ++$c) {
                        $value = $this->sdat[0][$c];
                        if (preg_match(self::$pnrhpatt, strtolower($value))) {
                                // 
                                // Check if row below actually contains a 
                                // personal number.
                                // 
                                $value = $this->sdat[1][$c];
                                if (preg_match(self::$pnrvpatt, $value)) {
                                        $this->first = 1;
                                        $this->readPersNr($c);
                                        return;
                                }
                        }
                }

                // 
                // Try to detect an personal number in each cell.
                // 
                for ($r = $this->first; $r < $this->rows; ++$r) {
                        for ($c = 0; $c < $this->cols; ++$c) {
                                $value = $this->sdat[$r][$c];
                                if (preg_match(self::$pnrvpatt, $value)) {
                                        $this->setValue(null, self::user, $value);
                                        $this->setValue($value, self::row, $r);
                                }
                        }
                }
        }

        // 
        // Read code from column index.
        // 
        private function readCode($column)
        {
                foreach (array_keys($this->students) as $user) {
                        $value = $this->sdat[$this->getValue($user, self::row)][$column];
                        $this->setValue($user, self::code, $value);
                }
        }

        // 
        // Read tag from column index.
        // 
        private function readTag($column)
        {
                foreach (array_keys($this->students) as $user) {
                        $value = $this->sdat[$this->getValue($user, self::row)][$column];
                        $this->setValue($user, self::tag, $value);
                }
        }

        // 
        // Assign the same tag to all students.
        // 
        private function assignTag($tag)
        {
                foreach (array_keys($this->students) as $user) {
                        $this->setValue($user, self::tag, $tag);
                }
        }

        // 
        // Resolve any personal numbers.
        // 
        private function lookupPersNr()
        {
                $ldap = new LdapSearch(LdapConnection::instance());
                $ldap->setAttributeFilter('uid');       // limit returned attr
                foreach ($this->students as $key => $val) {
                        if (is_numeric($key[0])) {
                                $user = (string) ($ldap->searchPersNr($key)->first()->getUid());
                                $val = $this->students[$key];
                                unset($this->students[$key]);
                                $this->students[$user] = $val;
                                $this->setvalue($user, self::pnr, $key);
                        }
                }
        }

        private function setValue($key, $name, $val)
        {
                if ($name == self::user) {
                        if (!isset($this->students[$val])) {
                                $this->students[$val] = array(
                                        self::tag  => '', self::code => ''
                                );
                        }
                } else {
                        $this->students[$key][$name] = $val;
                }
        }

        private function getValue($key, $name)
        {
                return $this->students[$key][$name];
        }

}

class ImportStudentsExcel2007 extends ImportStudents
{

        private static $mimedef = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Excel2007();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsExcel2003Xml extends ImportStudents
{

        private static $mimedef = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Excel2003XML();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsExcel97 extends ImportStudents
{

        private static $mimedef = "application/vnd.ms-excel";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Excel5();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsOpenDocument extends ImportStudents
{

        private static $mimedef = array(
                "application/vnd.oasis.opendocument.spreadsheet",
                "application/vnd.oasis.opendocument.spreadsheet-template");

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_OOCalc();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsGnumeric extends ImportStudents
{

        private static $mimedef = "application/x-gnumeric";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_Gnumeric();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsTextCsv extends ImportStudents
{

        private static $mimedef = "text/csv";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $this->reader = new PHPExcel_Reader_CSV();
                $this->reader->setReadDataOnly(true);
        }

}

class ImportStudentsTextTab extends ImportStudents
{

        private static $mimedef = "text/tab-separated-values";

        public function __construct($accept = "")
        {
                parent::__construct(self::$mimedef);
        }

        public function open()
        {
                $reader  =  PHPExcel_IOFactory::createReader('CSV');
                $reader->setDelimiter("\t");
                $this->reader->setReadDataOnly(true);
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
        public static function create($from, $type = null)
        {
                $extension = substr(strrchr($_FILES['file']['name'], "."), 1);

                if ($from == "pp") {
                        switch ($extension) {
                                case "xls":
                                        return new ImportPingPongExcel();
                                case "tab":
                                case "txt":
                                        return new ImportPingPongText();
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files. Please select an file containing a Ping-Ping question bank."), $extension));
                        }
                } elseif ($from == "oq" || $from == "op") {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExam();
                        }
                } elseif ($from == "sr") {       // student registrations
                        if ($type != -1) {
                                $find = $type;
                        } else {
                                $find = $extension;
                        }
                        switch ($find) {
                                case "excel2007":
                                case "xlsx":
                                        return new ImportStudentsExcel2007();
                                case "excel2003":
                                case "xml":
                                        return new ImportStudentsExcel2003Xml();
                                case "excel5":
                                case "excel97":
                                case "xls":
                                        return new ImportStudentsExcel97();
                                case "oocalc":
                                case "ods":
                                case "ots":
                                        return new ImportStudentsOpenDocument();
                                case "gnumeric":
                                        return new ImportStudentsGnumeric();
                                case "tab":
                                case "tsv":
                                case "txt":
                                        return new ImportStudentsTextTab();
                                case "csv":
                                        return new ImportStudentsTextCsv();
                                default:
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files. Please select an file containing a Ping-Ping question bank."), $extension));
                        }
                }
        }

}

?>
