<?php

// 
// Copyright (C) 2010-2013 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/manager.inc
// Author: Anders Lövgren
// Date:   2010-04-27
// 
// Support for managing exams.
// 

if (!defined("EXAM_STATE_CONTRIBUTABLE")) {     // It's still possible to contribute questions.
        define("EXAM_STATE_CONTRIBUTABLE", 1);
}
if (!defined("EXAM_STATE_EXAMINATABLE")) {      // Examination started, but new students can still be added.
        define("EXAM_STATE_EXAMINATABLE", 2);
}
if (!defined("EXAM_STATE_CORRECTABLE")) {       // Examination finished, not yet decoded.
        define("EXAM_STATE_CORRECTABLE", 4);
}
if (!defined("EXAM_STATE_DECODABLE")) {         // Examination can be decoded.
        define("EXAM_STATE_DECODABLE", 8);
}
if (!defined("EXAM_STATE_DECODED")) {           // Examination has been decoded.
        define("EXAM_STATE_DECODED", 16);
}
if (!defined("EXAM_STATE_EDITABLE")) {          // Examination is still fully editable.
        define("EXAM_STATE_EDITABLE", 32);
}

if (!defined("EXAM_STATE_UPCOMING")) {
        define("EXAM_STATE_UPCOMING", 64);
}
if (!defined("EXAM_STATE_RUNNING")) {
        define("EXAM_STATE_RUNNING", 128);
}
if (!defined("EXAM_STATE_FINISHED")) {
        define("EXAM_STATE_FINISHED", 256);
}

if (!defined("EXAM_STATE_TESTCASE")) {
        define("EXAM_STATE_TESTCASE", 512);
}
if (!defined("EXAM_STATE_LOCKDOWN")) {
        define("EXAM_STATE_LOCKDOWN", 1024);
}

// 
// An abstraction of exam grades.
// 
class ExamGrades
{

        private $data = array();

        //
        // Initialize using a serialized associative array (name => grade).
        //
        public function __construct($data = null)
        {
                if (isset($data) && strlen($data)) {
                        $this->decode($data);
                }
        }

        //
        // Get the array of grades.
        //
        public function getGrades()
        {
                return $this->data;
        }

        //
        // Get the grade name given the value (a score).
        //
        public function getGrade($value)
        {
                foreach ($this->data as $name => $grade) {
                        if ($value < $grade) {
                                return $prev;
                        }
                        $prev = $name;
                }
                return $name;
        }

        //
        // Get the threshold value for obtaining this grade.
        //
        public function getThreshold($grade)
        {
                return $this->data[$grade];
        }

        //
        // Return encoded form of this object.
        //
        public function encode()
        {
                return json_encode($this->data);
        }

        //
        // Set data for this object.
        //
        public function decode($data)
        {
                $this->data = (array) json_decode($data);
                asort($this->data);
        }

        //
        // Get human readable representation.
        //
        public function getText()
        {
                $str = "";
                foreach ($this->data as $name => $grade) {
                        $str .= sprintf("%s:%s\n", $name, $grade);      // grade are stored as string
                }
                return $str;
        }

        //
        // Initialize from text.
        //
        public function setText($text)
        {
                $this->data = array();
                $lines = split("\n", $text);
                foreach ($lines as $line) {
                        $line = trim($line);
                        if (strlen($line) != 0) {
                                list($name, $grade) = explode(":", $line);
                                $this->data[$name] = $grade;
                        }
                }
                asort($this->data);
        }

        //
        // For textual context:
        //
        public function __toString()
        {
                return $this->encode();
        }

}

//
// Utility class for what kind of details to expose from the examination when
// generating the result PDF.
// 
class ExamDetails
{

        private $mask = 0;  // The details mask
        //
        // The mask value is either an numeric or an array.

        //
        public function __construct($mask)
        {
                $this->mask = is_array($mask) ? array_sum($mask) : $mask;
        }

        public function getMask()
        {
                return $this->mask;
        }

}

class ExamState
{

        private $state;
        private $info;

        public function __construct($exam)
        {
                self::setInfo($exam);
                self::setState($exam);
        }

        private function setInfo($exam)
        {
                $sql = sprintf("SELECT  e.id AS ExamID,
                                        e.created AS ExamCreated,
                                        e.starttime AS ExamStartTime,
                                        e.endtime AS ExamEndTime,
                                        e.decoded AS ExamDecoded,
                                        e.testcase AS ExamTestCase,
                                        e.lockdown AS ExamLockDown,
                                        COUNT(a.id) AS AnswerCount
                                FROM exams e, questions q
                                        LEFT JOIN answers a ON q.id = a.question_id
                                WHERE e.id = %d AND q.exam_id = e.id", $exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $this->info = new DataRecord($res->fetchRow());
        }

        private function setState($exam)
        {
                if ($this->info->getExamDecoded() == 'Y') {
                        $this->state = EXAM_STATE_DECODED | EXAM_STATE_DECODABLE | EXAM_STATE_FINISHED;
                } else {
                        $manager = new Manager($exam);

                        $stime = strtotime($this->info->getExamStartTime());
                        $etime = strtotime($this->info->getExamEndTime());
                        $ctime = time();

                        if ($ctime < $stime) {                  // Before exam begins
                                $this->state = EXAM_STATE_CONTRIBUTABLE | EXAM_STATE_EXAMINATABLE | EXAM_STATE_EDITABLE | EXAM_STATE_UPCOMING;
                        } elseif ($ctime < $etime) {            // After exam begin, but before its finished
                                $this->state = EXAM_STATE_EXAMINATABLE | EXAM_STATE_RUNNING;
                        } elseif ($manager->isCorrected()) {    // After exam has finished
                                $this->state = EXAM_STATE_CORRECTABLE | EXAM_STATE_FINISHED | EXAM_STATE_DECODABLE;
                        } else {
                                $this->state = EXAM_STATE_CORRECTABLE | EXAM_STATE_FINISHED;
                        }
                }
                if ($this->info->getExamTestCase() == 'Y') {
                        $this->state |= EXAM_STATE_TESTCASE;
                }
                if ($this->info->getExamLockDown() == 'Y') {
                        $this->state |= EXAM_STATE_LOCKDOWN;
                }
        }

        public function getState()
        {
                return $this->state;
        }

        public function getInfo()
        {
                return $this->info;
        }

        public function hasAnswers()
        {
                return $this->info->getAnswerCount() != 0;
        }

        public function isContributable()
        {
                return $this->state & EXAM_STATE_CONTRIBUTABLE;
        }

        public function isExaminatable()
        {
                return $this->state & EXAM_STATE_EXAMINATABLE;
        }

        public function isCorrectable()
        {
                return $this->state & EXAM_STATE_CORRECTABLE;
        }

        public function isDecodable()
        {
                return $this->state & EXAM_STATE_DECODABLE;
        }

        public function isDecoded()
        {
                return $this->state & EXAM_STATE_DECODED;
        }

        public function isEditable()
        {
                return $this->state & EXAM_STATE_EDITABLE;
        }

        public function isUpcoming()
        {
                return $this->state & EXAM_STATE_UPCOMING;
        }

        public function isRunning()
        {
                return $this->state & EXAM_STATE_RUNNING;
        }

        public function isFinished()
        {
                return $this->state & EXAM_STATE_FINISHED;
        }

        public function isTestCase()
        {
                return $this->state & EXAM_STATE_TESTCASE;
        }

        public function isLockDown()
        {
                return $this->state & EXAM_STATE_LOCKDOWN;
        }

        public function readonly()
        {
                return !$this->isEditable();
        }

}

//
// Utility class for building trees.
// 
class TreeNode
{

        private $label;                 // Node text
        private $links = array();       // Optional links
        private $items = array();       // Optional text items
        private $childs = array();      // Array of TreeNode objects.
        private $link = null;

        public function __construct($label)
        {
                $this->label = $label;
        }

        public function setLink($url, $title = null)  // This node links to...
        {
                if (isset($title)) {
                        $this->link = array("href" => $url, "title" => $title);
                } else {
                        $this->link = array("href" => $url);
                }
        }

        public function addLink($name, $url, $title = null, $attr = null)
        {
                if (isset($title)) {
                        $this->links[$name] = array("href" => $url, "title" => $title);
                } else {
                        $this->links[$name] = array("href" => $url);
                }
                if (isset($attr)) {
                        foreach ($attr as $key => $value) {
                                $this->links[$name][$key] = $value;
                        }
                }
        }

        public function addText($text)
        {
                $this->items[] = $text;
        }

        public function addChild($child)
        {
                if (is_object($child)) {
                        $this->childs[] = $child;
                } else {
                        $child = new TreeNode($child);
                        $this->childs[] = $child;
                }
                return $child;
        }

        //
        // Add dates sub nodes to this tree node.
        //
        public function addDates($sdate, $edate)
        {
                if (date('Ymd', $sdate) == date('Ymd', $edate)) {
                        $this->addChild(sprintf("%s: %s %s - %s", _("Occasion"), strftime(DATE_FORMAT, $sdate), strftime(TIME_FORMAT, $sdate), strftime(TIME_FORMAT, $edate)));
                } else {
                        $this->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, $sdate)));
                        $this->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, $edate)));
                }
        }

        public function getChilds()
        {
                return $this->childs;
        }

        private function attr($attr)
        {
                $str = "";
                foreach ($attr as $name => $value) {
                        $str .= sprintf("%s=\"%s\" ", $name, $value);
                }
                return $str;
        }

        public function output()
        {
                //
                // Output this node and any child nodes.
                //
                if (count($this->links) != 0) {
                        printf("<ul><li class=\"truncate\" style=\"width: %dpx;\">", 550 - count($this->links) * 50);
                } else {
                        printf("<ul><li>");
                }
                if (isset($this->link)) {
                        printf("<a %s>%s</a>", $this->attr($this->link), $this->label);
                } else {
                        printf("%s", $this->label);
                }
                foreach ($this->items as $text) {
                        printf("<br />%s", $text);
                }
                $links = array();
                if (count($this->links) != 0) {
                        printf("<span class=\"links\">");
                        foreach ($this->links as $name => $attr) {
                                $links[] = sprintf("<a %s>%s</a>", $this->attr($attr), $name);
                        }
                        printf("%s</span>", implode(", ", $links));
                }
                printf("</li>");
                foreach ($this->childs as $child) {
                        $child->output();
                }
                printf("</ul>\n");
        }

}

class TreeBuilder
{

        private $root;   // The root node

        public function __construct($label)
        {
                $this->root = new TreeNode($label);
        }

        public function getRoot()
        {
                return $this->root;
        }

        public function output()
        {
                $this->root->output();
        }

}

class Manager
{

        private $exam;   // Exam ID
        private $info = null;

        public function __construct($exam)
        {
                $this->exam = $exam;
        }

        public function getExamID()
        {
                return $this->exam;
        }

        //
        // Get all exams where this user is the manager for.
        //
        public static function getExams($user, $order = "starttime", $collate = "DESC")
        {
                $sql = sprintf("SELECT  id AS ExamID,
                                        name AS ExamName,
                                        descr AS ExamDescription,
                                        starttime AS ExamStartTime,
                                        endtime AS ExamEndTime,
                                        created AS ExamCreated,
                                        updated AS ExamUpdated,
                                        creator AS ExamCreator,
                                        decoded AS ExamDecoded,
                                        grades AS ExamGrades,
                                        testcase AS ExamTestCase,
                                        lockdown AS ExamLockDown
                                FROM exams
                                WHERE creator = '%s'
                                ORDER BY %s %s", $user, $order, $collate);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get meta information about the exam. This is used by the various teacher script
        // to evaluate whether an operation like contribute or decode can be performed.
        //
        public function getInfo($flush = false)
        {
                if (!isset($this->info) || $flush) {
                        $this->info = new ExamState($this->exam);
                }
                return $this->info;
        }

        //
        // Get data for this exam.
        //
        public function getData()
        {
                $sql = sprintf("SELECT  id AS ExamID,
                                        name AS ExamName,
                                        descr AS ExamDescription,
                                        orgunit AS ExamOrgUnit,
                                        starttime AS ExamStartTime,
                                        endtime AS ExamEndTime,
                                        created AS ExamCreated,
                                        updated AS ExamUpdated,
                                        creator AS ExamCreator,
                                        decoded AS ExamDecoded,
                                        grades AS ExamGrades,
                                        details AS ExamDetails,
                                        testcase AS ExamTestCase,
                                        lockdown AS ExamLockDown
                                FROM exams WHERE id = %d", $this->exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // Set data for this exam or create a new exam in the database (if exam ID is 0).
        //
        public function setData($unit, $name, $desc, $grades, $details, $sdate, $edate)
        {
                $db = Database::getConnection();

                if ($this->exam != 0) {
                        $sql = sprintf("UPDATE exams SET orgunit = '%s', name = '%s', descr = '%s', grades = '%s', details = %d, starttime = '%s', endtime = '%s'
                                        WHERE id = '%d'", $db->escape($unit), $db->escape($name), $db->escape($desc), $grades, $details, date('Y-m-d H:i:s', $sdate), date('Y-m-d H:i:s', $edate), $this->exam);
                } else {
                        $sql = sprintf("INSERT INTO exams(orgunit, name, descr, grades, details, starttime, endtime, created, creator)
                                        VALUES('%s','%s','%s','%s',%d,'%s','%s','%s','%s')", $db->escape($unit), $db->escape($name), $db->escape($desc), $grades, $details, date('Y-m-d H:i:s', $sdate), date('Y-m-d H:i:s', $edate), date('Y-m-d H:i:s', time()), phpCAS::getUser());
                }
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                if ($this->exam == 0) {
                        $this->exam = $db->lastInsertId("exams", "id");

                        //
                        // The new exam must have an topics record.
                        // 
                        $sql = sprintf("INSERT INTO topics(exam_id, name)
                                        VALUES(%d, 'default')", $this->exam);
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                throw new DatabaseException($res->getMessage());
                        }
                }
        }

        //
        // Returns true if exam is a dugga, that is, the questions for the
        // exam is selected by random from a bank of questions divided into
        // topics.
        //
        public function isDugga()
        {
                $sql = sprintf("SELECT SUM(randomize) AS random
                                FROM topics WHERE exam_id = %d", $this->exam);

                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                return $row['random'] != 0;
        }

        //
        // Returns true if all answers have been corrected.
        //
        public function isCorrected()
        {
                //
                // Get number of answer without a corresponding result record:
                //
                $sql = sprintf("SELECT  COUNT(a.id)
                                FROM    students s, answers a
                                        LEFT JOIN results r ON a.id = r.answer_id
                                WHERE   s.exam_id = %d AND
                                        s.id = a.student_id AND
                                        a.answered = 'Y' AND
                                        r.id IS NULL", $this->exam);
                $db = Database::getConnection();
                $db->setFetchMode(MDB2_FETCHMODE_ORDERED);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
                return intval($row[0]) == 0;
        }

        //
        // Returns true if the user has been assigned the requested role on this
        // exam. These roles are bound to an exam: creator, contributor, examinator
        // or decoder. The corrector role is granted to a person by an questions
        // publisher. Use cached query result if cache is true.
        //
        public function hasRole($user, $role, $cache = true)
        {
                if ($cache && isset($this->$user->$role)) {
                        return $this->$user->$role;
                }

                switch ($role) {
                        case "creator":
                                $sql = sprintf("SELECT COUNT(*) FROM exams 
                                                WHERE id = %d AND creator = '%s'", $this->exam, $user);
                                break;
                        case "contributor":
                        case "examinator":
                        case "decoder":
                                $sql = sprintf("SELECT COUNT(*) FROM ${role}s
                                                WHERE exam_id = %d AND user = '%s'", $this->exam, $user);
                                break;
                        case "corrector":
                                $sql = sprintf("SELECT COUNT(*) FROM questions 
                                                WHERE exam_id = %d AND user = '%s'", $this->exam, $user);
                                break;
                        default:
                                return false;
                }

                $db = Database::getConnection();
                $db->setFetchMode(MDB2_FETCHMODE_ORDERED);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                if ($cache) {
                        if (!isset($this->$user)) {
                                $this->$user = new stdClass();
                        }
                        $this->$user->$role = intval($row[0]) >= 1;
                }

                $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
                return intval($row[0]) >= 1;
        }

        //
        // Return true if this user is the creator this exam.
        //
        public function isCreator($user)
        {
                return self::hasRole($user, "creator");
        }

        //
        // Return true if this user is a corrector for this exam.
        //
        public function isCorrector($user)
        {
                return self::hasRole($user, "corrector");
        }

        //
        // Return true if this user is a contributor for this exam.
        //
        public function isContributor($user)
        {
                return self::hasRole($user, "contributor");
        }

        //
        // Return true if this user is a decoder for this exam.
        //
        public function isDecoder($user)
        {
                return self::hasRole($user, "decoder");
        }

        //
        // Return true if this user is a examinator for this exam.
        //
        public function isExaminator($user)
        {
                return self::hasRole($user, "examinator");
        }

        //
        // Return true if user can create and run test cases.
        //
        public function isTestCaseAllowed($user)
        {
                if (self::hasRole($user, "creator")) {
                        return true;
                }
                if (self::hasRole($user, "contributor")) {
                        return TEST_CASE_ALLOW_CONTRIBUTOR;
                }
                return false;
        }

        //
        // Get all users granted contributor rights on this exam.
        //
        public function getContributors()
        {
                $sql = sprintf("SELECT  id AS ContributorID,
                                        exam_id AS ExamID,
                                        user AS ContributorUser
                                FROM contributors
                                WHERE exam_id = %d", $this->exam);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get all users granted examinator rights on this exam.
        //
        public function getExaminators()
        {
                $sql = sprintf("SELECT  id AS ExaminatorID,
                                        exam_id AS ExamID,
                                        user AS ExaminatorUser
                                FROM examinators
                                WHERE exam_id = %d", $this->exam);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get all users granted decoder rights on this exam.
        //
        public function getDecoders()
        {
                $sql = sprintf("SELECT  id AS DecoderID,
                                        exam_id AS ExamID,
                                        user AS DecoderUser
                                FROM decoders
                                WHERE exam_id = %d", $this->exam);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get all questions associated with this exam, with optional filtering
        // on status. If status is null, then all question are returned.
        // Possible values for filter are: 'active', 'removed' or 'own'.
        //
        // If student != 0, then only questions assigned to this student is
        // returned.
        //
        public function getQuestions($filter = null, $student = 0)
        {
                if ($student != 0) {
                        $sql = sprintf("SELECT  q.id AS QuestionID,
                                                t.id AS TopicID,
                                                q.exam_id AS ExamID,
                                                q.score AS QuestionScore,
                                                q.name AS QuestionName,
                                                q.quest AS QuestionText,
                                                q.user AS QuestionPublisher,
                                                q.video AS QuestionVideo,
                                                q.image AS QuestionImage,
                                                q.audio AS QuestionAudio,
                                                q.type AS QuestionType,
                                                q.status AS QuestionStatus,
                                                q.comment AS QuestionComment,
                                                t.name AS TopicName,
                                                t.randomize AS TopicRandom
                                        FROM    topics t
                                        LEFT JOIN questions q
                                        ON      q.topic_id = t.id AND
                                                q.exam_id = t.exam_id
                                        LEFT JOIN answers a
                                        ON      q.id = a.question_id
                                        WHERE   t.exam_id = %d AND
                                                a.student_id = %d
                                        ORDER BY QuestionID", $this->exam, $student);
                } else {
                        $sql = sprintf("SELECT  q.id AS QuestionID,
                                                t.id AS TopicID,
                                                q.exam_id AS ExamID,
                                                q.score AS QuestionScore,
                                                q.name AS QuestionName,
                                                q.quest AS QuestionText,
                                                q.user AS QuestionPublisher,
                                                q.video AS QuestionVideo,
                                                q.image AS QuestionImage,
                                                q.audio AS QuestionAudio,
                                                q.type AS QuestionType,
                                                q.status AS QuestionStatus,
                                                q.comment AS QuestionComment,
                                                t.name AS TopicName,
                                                t.randomize AS TopicRandom
                                        FROM    topics t LEFT JOIN questions q
                                        ON      q.topic_id = t.id AND
                                                q.exam_id = t.exam_id
                                        WHERE   t.exam_id = %d", $this->exam);
                        if (isset($filter)) {
                                if ($filter == "active" || $filter == "removed") {
                                        $sql .= sprintf(" AND q.status = '%s'", $filter);
                                } elseif ($filter == "own") {
                                        $sql .= sprintf(" AND q.user = '%s'", phpCAS::getUser());
                                }
                        }
                        $sql .= " ORDER BY TopicID, QuestionID";
                }

                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get the list of students assigned to this exam.
        //
        public function getStudents()
        {
                $sql = sprintf("SELECT  id AS StudentID,
                                        exam_id AS ExamID,
                                        user AS StudentUser,
                                        code AS StudentCode
                                FROM    students
                                WHERE   exam_id = %d
                                ORDER BY user", $this->exam);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get data for this student.
        //
        public function getStudentData($student)
        {
                $sql = sprintf("SELECT  id AS StudentID,
                                        exam_id AS ExamID,
                                        user AS StudentUser,
                                        code AS StudentCode
                                FROM    students
                                WHERE   id = %d", $student);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // These function are used for granting and revoking permissions on this exam.
        //

        private function grantExamRole($user, $role)
        {
                $sql = sprintf("INSERT INTO %ss(exam_id, user)
                                VALUES(%d, '%s')", $role, $this->exam, $user);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        private function revokeExamRole($user, $role)
        {
                $sql = sprintf("DELETE FROM %ss 
                                WHERE   exam_id = %d AND
                                        id = '%s'", $role, $this->exam, $user);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        public function addContributor($user)
        {
                $this->grantExamRole($user, "contributor");
        }

        public function deleteContributor($user)
        {
                $this->revokeExamRole($user, "contributor");
        }

        public function addExaminator($user)
        {
                $this->grantExamRole($user, "examinator");
        }

        public function deleteExaminator($user)
        {
                $this->revokeExamRole($user, "examinator");
        }

        public function addDecoder($user)
        {
                $this->grantExamRole($user, "decoder");
        }

        public function deleteDecoder($user)
        {
                $this->revokeExamRole($user, "decoder");
        }

        //
        // This function creates a clone of this exam and returns the clone object.
        //
        public function copy($testcase = false)
        {
                $db = Database::getConnection();
                if ($db->supports('transaction')) {
                        $db->beginTransaction();
                }

                //
                // Create the clone object.
                //
                $data = $this->getData();
                if ($testcase) {
                        $data->setExamStartTime(time() - 1);
                        $data->setExamEndTime(strtotime(DATETIME_NONE));
                } else {
                        $data->setExamStartTime(strtotime(DATETIME_NONE));
                        $data->setExamEndTime(strtotime(DATETIME_NONE));
                }
                $copy = new Manager(0);
                $copy->setData($data->getExamOrgUnit(), $data->getExamName(), $data->getExamDescription(), $data->getExamGrades(), $data->getExamDetails(), $data->getExamStartTime(), $data->getExamEndTime());

                if (!$testcase) {
                        //
                        // Duplicate the assigned contributor, examinator and decoder roles.
                        //
                        foreach (array("decoders", "examinators", "contributors") as $table) {
                                $sql = sprintf("INSERT INTO %s(exam_id, user)
                                                SELECT %d, user
                                                FROM %s
                                                WHERE id = %d", $table, $copy->getExamID(), $table, $this->getExamID());
                                $res = $db->query($sql);
                                if (PEAR::isError($res)) {
                                        if ($db->in_transaction) {
                                                $db->rollback();
                                        }
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                }

                //
                // Add prefered roles to caller:
                //
                if (!$testcase) {
                        if (!$copy->isContributor(phpCAS::getUser())) {
                                $copy->addContributor(phpCAS::getUser());
                        }
                        if (!$copy->isDecoder(phpCAS::getUser())) {
                                $copy->addDecoder(phpCAS::getUser());
                        }
                } else {
                        if (!$copy->isExaminator(phpCAS::getUser())) {
                                $copy->addExaminator(phpCAS::getUser());
                        }
                        if (!$copy->isDecoder(phpCAS::getUser())) {
                                $copy->addDecoder(phpCAS::getUser());
                        }
                }

                //
                // Copy all questions, but without any associated answers, question status or comments.
                //
                $sql = sprintf("INSERT INTO questions(exam_id, topic_id, score, name, quest, user, video, image, audio, type)
                                SELECT  %d, topic_id, score, name, quest, user, video, image, audio, type
                                FROM    questions
                                WHERE   exam_id = %d", $copy->getExamID(), $this->getExamID());
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        if ($db->in_transaction) {
                                $db->rollback();
                        }
                        throw new DatabaseException($res->getMessage());
                }

                try {
                        $sql = sprintf("DELETE FROM topics WHERE exam_id = %d", $copy->getExamID());
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                throw new DatabaseException($res->getMessage());
                        }

                        //
                        // Copy all topics. I can't figure out how this can be done
                        // without looping thru the current topics on this exam.
                        //
                        $sql = sprintf("SELECT  id AS TopicID 
                                        FROM    topics
                                        WHERE   exam_id = %d", $this->getExamID());
                        $topics = new ResultSet($db->query($sql));

                        foreach ($topics as $topic) {
                                $sql = sprintf("INSERT INTO topics(exam_id, name, randomize) 
                                                SELECT  %d, name, randomize
                                                FROM    topics
                                                WHERE   id = %d", $copy->getExamID(), $topic->getTopicID());
                                $res = $db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                                $tid = $db->lastInsertID("topics");
                                $sql = sprintf("UPDATE  questions
                                                SET     topic_id = %d
                                                WHERE   topic_id = %d AND
                                                        exam_id = %d", $tid, $topic->getTopicID(), $copy->getExamID());
                                $res = $db->query($sql);
                                if (PEAR::isError($res)) {
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                } catch (DatabaseException $exception) {
                        if ($db->in_transaction) {
                                $db->rollback();
                        }
                        throw $exception;
                }

                if ($testcase) {
                        //
                        // The exam must be flagged as a test case.
                        //
                        $sql = sprintf("UPDATE exams SET testcase = 'Y', lockdown = 'N'
                                        WHERE id = %d", $copy->getExamID());
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                if ($db->in_transaction) {
                                        $db->rollback();
                                }
                                throw new DatabaseException($res->getMessage());
                        }

                        //
                        // Add caller as an participient on this exam:
                        //
                        $sql = sprintf("INSERT INTO students(exam_id, user, code)
                                        VALUES(%d, '%s', '%s')", $copy->getExamID(), phpCAS::getUser(), sprintf("TEST%.04d", $copy->getExamID()));
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                if ($db->in_transaction) {
                                        $db->rollback();
                                }
                                throw new DatabaseException($res->getMessage());
                        }
                }

                if ($testcase) {
                        // 
                        // Simply add a symbolic link to media files.
                        // 
                        $media = new MediaLibrary($this->getExamID());
                        $media->link($copy->getExamID(), MediaLibrary::resource);
                } else {
                        // 
                        // Perform a real copy.
                        // 
                        $media = new MediaLibrary($this->getExamID());
                        $media->copy($copy->getExamID(), MediaLibrary::resource);
                }

                if ($db->in_transaction) {
                        $db->commit();
                }

                return $copy;
        }

        //
        // Delete this exam. Referental integrity should ensure that this function fails
        // if questions have answers or if exam has students with answers.
        //
        public function delete($testcase = false)
        {
                $db = Database::getConnection();
                if ($db->supports('transaction')) {
                        $db->beginTransaction();
                } else {
                        throw new DatabaseException("The exam can't be safely deleted");
                }

                //
                // Only remove answers in test case mode to ensure referential integrity.
                //
                if ($testcase) {
                        $questions = $this->getQuestions();
                        foreach ($questions as $question) {
                                //
                                // Delete results first for answers to this question:
                                //
                                $sql = sprintf("DELETE r FROM results AS r
                                                LEFT JOIN answers AS a ON r.answer_id = a.id
                                                WHERE a.question_id = %d", $question->getQuestionID());
                                $res = $db->query($sql);
                                if (PEAR::isError($res)) {
                                        if ($db->in_transaction) {
                                                $db->rollback();
                                        }
                                        throw new DatabaseException($res->getMessage());
                                }

                                //
                                // Now delete the answers to this question:
                                //
                                $sql = sprintf("DELETE FROM answers 
                                                WHERE question_id = %d", $question->getQuestionID());
                                $res = $db->query($sql);
                                if (PEAR::isError($res)) {
                                        if ($db->in_transaction) {
                                                $db->rollback();
                                        }
                                        throw new DatabaseException($res->getMessage());
                                }
                        }
                }

                foreach (array("contributor", "examinator", "decoder", "student") as $role) {
                        $sql = sprintf("DELETE FROM %ss
                                        WHERE exam_id = %d", $role, $this->exam);
                        printf("sql: $sql\n");
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                if ($db->in_transaction) {
                                        $db->rollback();
                                }
                                throw new DatabaseException($res->getMessage());
                        }
                }

                $sql = sprintf("DELETE FROM questions
                                WHERE exam_id = %d", $this->exam);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        if ($db->in_transaction) {
                                $db->rollback();
                        }
                        throw new DatabaseException($res->getMessage());
                }

                $sql = sprintf("DELETE FROM topics
                                WHERE exam_id = %d", $this->exam);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        if ($db->in_transaction) {
                                $db->rollback();
                        }
                        throw new DatabaseException($res->getMessage());
                }

                $sql = sprintf("DELETE FROM exams
                                WHERE id = %d", $this->exam);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        if ($db->in_transaction) {
                                $db->rollback();
                        }
                        throw new DatabaseException($res->getMessage());
                }

                if ($db->in_transaction) {
                        $db->commit();
                }

                // 
                // Delete all media and resource files:
                // 
                $media = new MediaLibrary($this->exam);
                $media->cleanup();
        }

}

?>
