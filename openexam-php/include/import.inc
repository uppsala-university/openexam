<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/import.inc
// Author: Anders Lövgren
// Date:   2010-12-09
//
// Import utility.
//

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

//
// The interface for concrete import classes. Currently we are only using
// this interface for importing question data to an exam.
//
interface Import
{

        //
        // Open file and prepare to read it.
        //
        function open();

        //
        // Parse current opened import file and save data to exam.
        //
        // If exam is exam == 0, then a new exam is created, otherwise the
        // selected exam is updated. This is not supported by all import
        // targets (currently only openexam projects).
        //
        // Returns the exam ID.
        //
        function read($exam, &$db);

        //
        // Close opened import file.
        //
        function close();

        //
        // Prototype the constructor:
        //
        function __construct($name, $file, $mime, $size, $accept = "");
}

abstract class ImportBase implements Import
{

        protected $name;
        protected $file;
        protected $mime;
        protected $size;
        protected $user;

        public function __construct($name, $file, $mime, $size, $accept = "")
        {
                if (isset($mime) && $mime != $accept) {
                        throw new ImportException(sprintf(_("Wrong MIME type (%s) on uploaded file %s (expected %s)"), $mime, $name, $accept));
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

        //
        // Remove HTML tags and other junk from the input strings. This function
        // should be called on for any text field read from the excel-file.
        //
        public function cleanup($str)
        {
                return utf8_encode(strip_tags(html_entity_decode($str)));
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

abstract class ImportInsert
{
        const questions = 1;    // Insert questions
        const project = 2;      // Insert/update project

        protected $exam;
        protected $db;

        public function __construct($exam, &$db)
        {
                $this->exam = $exam;
                $this->db = $db;
        }

        public function getExamID()
        {
                return $this->exam;
        }

        abstract public function insert(&$data, $what = self::questions);
}

//
// Insert data imported from ping-pong.
//
class ImportInsertPingPong extends ImportInsert
{

        public function insert(&$data, $what = self::questions)
        {
                foreach ($data as $topic => $questions) {
                        $sql = sprintf("INSERT INTO topics(exam_id, name)
                                        VALUES(%d, '%s')",
                                        $this->exam, $topic);
                        $res = $this->db->query($sql);
                        if (PEAR::isError($res)) {
                                throw new DatabaseException($res->getMessage());
                        }
                        $tid = $this->db->lastInsertID();
                        foreach ($questions as $question) {
                                if ($question['type'] == 'multiple' || $question == 'single') {
                                        $question['quest'] = sprintf("%s\n\n%s", $question['body'], json_encode($question['choice']));
                                        $question['quest'] = $this->db->escape($question['quest']);
                                } else {
                                        $question['quest'] = $this->db->escape($question['body']);
                                }

                                $sql = sprintf("INSERT INTO questions(
                                                                exam_id,
                                                                topic_id,
                                                                score,
                                                                name,
                                                                quest,
                                                                type,
                                                                user,
                                                                comment)
                                                VALUES(%d,%d,%f,'%s','%s','%s','%s','%s')",
                                                $this->exam,
                                                $tid,
                                                $question['score'],
                                                $question['name'],
                                                $question['quest'],
                                                $question['type'],
                                                $question['user'],
                                                $question['comment']);

                                $res = $this->db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                }
        }

}

//
// Insert openexam project data.
//
class ImportInsertOpenExam extends ImportInsert
{

        //
        // The data parameter is an SimpleXML object.
        //
        public function insert(&$data, $what = self::questions)
        {
                if ($what & self::project) {
                        if ($data['format'] == 6071) {
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
                                                        WHERE   exam_id = %d",
                                                        $data->project->name,
                                                        $data->project->description,
                                                        $data->project->orgunit,
                                                        $data->project->starttime,
                                                        $data->project->endtime,
                                                        $data->project->created,
                                                        $data->project->grades,
                                                        $this->exam);
                                        $res = $this->db->query($sql);
                                        if (PEAR::isError($res)) {
                                                throw new DatabaseException($res->getMessage());
                                        }
                                } else {
                                        $sql = sprintf("INSERT INTO exams(name, descr, orgunit, starttime, endtime, created, creator, grades)
                                                        VALUES('%s','%s','%s','%s','%s','%s','%s','%s')",
                                                        $data->project->name,
                                                        $data->project->description,
                                                        $data->project->orgunit,
                                                        $data->project->starttime,
                                                        $data->project->endtime,
                                                        $data->project->created,
                                                        phpCAS::getUser(),
                                                        $data->project->grades);
                                        $res = $this->db->query($sql);
                                        if (PEAR::isError($res)) {
                                                throw new DatabaseException($res->getMessage());
                                        }
                                        $this->exam = $this->db->lastInsertID("exams");
                                }
                        }
                }

                if ($what & self::questions) {
                        //
                        // Insert topics:
                        //
                        $topicmap = array();
                        foreach ($data->topics->topic as $topic) {
                                $sql = sprintf("INSERT INTO topics(exam_id, name, randomize)
                                                VALUES(%d,'%s','%s')",
                                                $this->exam,
                                                $topic->name,
                                                $topic->randomize);
                                $res = $this->db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                                $topicmap[(int) $topic['id']] = $this->db->lastInsertID("topics");
                        }

                        //
                        // Insert questions:
                        //
                        foreach ($data->questions->question as $question) {
                                $question->quest = $this->db->escape($question->text);
                                $sql = sprintf("INSERT INTO questions(exam_id, topic_id, score, name, quest, user, video, image, audio, type, status, comment)
                                                VALUES(%d,%d,%f,'%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                                                $this->exam,
                                                $topicmap[(int) $question['topic']],
                                                $question->score,
                                                $question->name,
                                                $question->quest,
                                                $question->publisher,
                                                $question->video,
                                                $question->image,
                                                $question->audio,
                                                $question->type,
                                                $question->status,
                                                $question->comment);
                                $res = $this->db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                }
        }

}

class ImportPingPongExcel extends ImportBase
{
        const format = "QI625";
        const expect = "Exported from the questionbank in PING PONG";
        const accept = "application/vnd.ms-excel";
        const encoding = "CP1251";

        private $data;

        public function __construct($name, $file, $mime, $size, $accept = null)
        {
                parent::__construct($name, $file, $mime, $size, self::accept);
        }

        public function open()
        {
                $this->data = new Spreadsheet_Excel_Reader();
                $this->data->setOutputEncoding(self::encoding);
        }

        public function read($exam, &$db)
        {
                $this->data->read($this->file);

                $sheet->rows = $this->data->sheets[0]['numRows'];
                $sheet->cols = $this->data->sheets[0]['numCols'];
                $sheet->cell = $this->data->sheets[0]['cells'];

                if ($sheet->cell[1][1] != self::expect) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::expect);
                        throw new ImportException($message);
                }
                if ($sheet->cell[2][2] != self::format) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::format);
                        throw new ImportException($message);
                }

                $questions = array();
                $question = null;
                $category = null;

                for ($i = 1; $i <= $sheet->rows; $i++) {
                        if (!isset($sheet->cell[$i])) {
                                continue;
                        }
                        if ($sheet->cell[$i][1] == "Question") {
                                if (isset($question)) {
                                        $questions[$category][] = $question;
                                }
                                $question = array("comment" => "", "score" => 1.0, "user" => $this->user);
                        }
                        if ($sheet->cell[$i][1] == "Category") {
                                $category = $this->cleanup($sheet->cell[$i][2]);
                        }
                        if ($sheet->cell[$i][1] == "Name") {
                                $question['name'] = $this->cleanup($sheet->cell[$i][2]);
                        }
                        if ($sheet->cell[$i][1] == "Description") {
                                $question['comment'] = $this->cleanup($sheet->cell[$i][2]);
                        }
                        if ($sheet->cell[$i][1] == "Text") {
                                $question['body'] = $this->cleanup($sheet->cell[$i][2]);
                        }
                        if ($sheet->cell[$i][1] == "Max points") {
                                $question['score'] = $sheet->cell[$i][2];
                        }
                        if ($sheet->cell[$i][1] == "Type") {
                                if ($sheet->cell[$i][2] == "Multiple choice") {
                                        $question['type'] = "multiple";
                                        $question['choice'] = array();
                                } elseif ($sheet->cell[$i][2] == "Single choice") {
                                        $question['type'] = "single";
                                        $question['choice'] = array();
                                }
                        }
                        if ($sheet->cell[$i][1] == "Correct") {
                                $question['choice'][$this->cleanup($sheet->cell[$i][2])] = true;
                        }
                        if ($sheet->cell[$i][1] == "Incorrect") {
                                $question['choice'][$this->cleanup($sheet->cell[$i][2])] = false;
                        }
                }

                $inserter = new ImportInsertPingPong($exam, $db);
                $inserter->insert($questions);

                return $inserter->getExamID();
        }

}

class ImportPingPongText extends ImportBase
{
        const format = "QI625";
        const expect = "Exported from the questionbank in PING PONG";
        const accept = "text/plain";
        const delimiter = "\t";

        private $stream;

        public function __construct($name, $file, $mime, $size, $accept = null)
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
                }
                return explode(self::delimiter, $str);
        }

        public function read($exam, &$db)
        {
                if (($data = $this->next()) && $data[0] != self::expect) {
                        $message = sprintf(_("Expected header '%s' at index (1,1)"), self::expect);
                        throw new ImportException($message);
                }

                if (($data = $this->next()) && $data[1] != self::format) {
                        $message = sprintf(_("Expected format '%s' at index (2,2)"), self::format);
                        throw new ImportException($message);
                }

                $questions = array();
                $question = null;
                $category = null;

                while ($data = $this->next()) {
                        if (count($data) == 0) {
                                continue;
                        }
                        if ($data[0] == "Question") {
                                if (isset($question)) {
                                        $questions[$category][] = $question;
                                }
                                $question = array("comment" => "", "score" => 1.0, "user" => $this->user);
                        }
                        if ($data[0] == "Category") {
                                $category = $this->cleanup($data[1]);
                        }
                        if ($data[0] == "Name") {
                                $question['name'] = $this->cleanup($data[1]);
                        }
                        if ($data[0] == "Description") {
                                $question['comment'] = $this->cleanup($data[1]);
                        }
                        if ($data[0] == "Text") {
                                $question['body'] = $this->cleanup($data[1]);
                        }
                        if ($data[0] == "Max points") {
                                $question['score'] = $data[1];
                        }
                        if ($data[0] == "Type") {
                                if ($data[1] == "Multiple choice") {
                                        $question['type'] = "multiple";
                                        $question['choice'] = array();
                                } elseif ($data[1] == "Single choice") {
                                        $question['type'] = "single";
                                        $question['choice'] = array();
                                }
                        }
                        if ($data[0] == "Correct") {
                                $question['choice'][$this->cleanup($data[1])] = true;
                        }
                        if ($data[0] == "Incorrect") {
                                $question['choice'][$this->cleanup($data[1])] = false;
                        }
                }

                $inserter = new ImportInsert($exam, $db);
                $inserter->insert($questions);

                return $inserter->getExamID();
        }

        public function close()
        {
                fclose($this->stream);
        }

}

abstract class ImportOpenExam extends ImportBase
{
        const accept = "application/xml";

        protected $xmldoc;      // SimpleXML document object

        public function __construct($name, $file, $mime, $size, $accept = null)
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ImportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ImportException("The DOM Document extension (dom) is not loaded");
                }
                parent::__construct($name, $file, $mime, $size, self::accept);
        }

        public function open()
        {
                $this->xmldoc = simplexml_load_file($this->file);
                $this->verify();
        }

        //
        // Verify that loaded document can be imported.
        //
        private function verify()
        {
                if (!isset($this->xmldoc['format'])) {
                        $message = _("Missing XML format attribute. This don't look like an OpenExam project data file.");
                        throw new ImportException($message);
                }

                $supported = false;
                foreach (array(6071) as $format) {
                        if ($this->xmldoc['format'] == $format) {
                                $supported = true;
                        }
                }
                if (!$supported) {
                        $message = sprintf(_("Unsupported XML format number: %d. This file can not be imported."), $this->xmldoc['@attributes']['format']);
                        throw new ImportException($message);
                }
        }

}

//
// Import OpenExam project. See include/export.inc for schema information.
//
class ImportOpenExamProject extends ImportOpenExam
{

        function read($exam, &$db)
        {
                $inserter = new ImportInsertOpenExam($exam, $db);
                $inserter->insert($this->xmldoc, ImportInsert::project | ImportInsert::questions);
                return $inserter->getExamID();
        }

}

//
// Import OpenExam questions. See include/export.inc for schema information.
//
class ImportOpenExamQuestions extends ImportOpenExam
{

        function read($exam, &$db)
        {
                $inserter = new ImportInsertOpenExam($exam, $db);
                $inserter->insert($this->xmldoc, ImportInsert::questions);
                return $inserter->getExamID();
        }

}

class FileImport
{

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
        //
        public static function getReader($type, $name, $file, $mime, $size)
        {
                $extension = substr(strrchr($name, "."), 1);

                if (!is_uploaded_file($file)) {
                        throw new ImportException(sprintf(_("Possible file upload attack: %s"), $file));
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
                } elseif ($type == "oq") {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExamQuestions($name, $file, $mime, $size);
                                        break;
                        }
                } elseif ($type == "op") {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExamProject($name, $file, $mime, $size);
                                        break;
                        }
                }
        }

}

?>
