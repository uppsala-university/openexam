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

}

class ImportInsert
{

        private $exam;
        private $db;

        public function __construct($exam, &$db)
        {
                $this->exam = $exam;
                $this->db = $db;
        }

        public function insert(&$data)
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
                                //
                                // json_encode() is trashing data by converting UTF8 to \uxxx encoding. These
                                // gets stripped when inserting in the database, so we have to encode the
                                // data by ourself.
                                //
                                if ($question['type'] == 'multiple' || $question == 'single') {
                                        $pairs = array();
                                        foreach ($question['choice'] as $key => $val) {
                                                $pairs[] = sprintf("\"%s\":%s", $key, $val ? "true" : "false");
                                        }
                                        $question['quest'] = sprintf("%s\n\n{%s}",
                                                        $question['body'],
                                                        implode(",", $pairs));
                                } else {
                                        $question['quest'] = $question['body'];
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
                        $message = sprintf(_("Expected header '%s' in cell (1,1)"), self::expect);
                        throw new ImportException($message);
                }
                if ($sheet->cell[2][2] != self::format) {
                        $message = sprintf(_("Expected format '%s' in cell (2,2)"), self::format);
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

                $inserter = new ImportInsert($exam, $db);
                $inserter->insert($questions);
        }

        public function close()
        {
                // unlink($this->file);
        }

}

//
// TODO: implement import classes
//
//class ImportPingPongText implements Import
//{
//
//}
//
//class ImportOpenExam implements Import
//{
//
//}

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
        //   oe - open exam exported questions
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
                                        throw new ImportException(sprintf(_("Don't know how import *.%s files"), $extension));
                                        break;
                        }
                } elseif ($type == "oe") {
                        switch ($extension) {
                                default:
                                        return new ImportOpenExam($name, $file, $mime,
                                                $size
                                        );
                                        break;
                        }
                }
        }

}

?>
