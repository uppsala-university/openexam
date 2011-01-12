<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/exam.inc
// Author: Anders Lövgren
// Date:   2010-04-21
// 
// Support class for the online examination.
//
// 
// Define symbolic names for question types:
// 
if (!defined("QUESTION_TYPE_FREETEXT")) {
        define("QUESTION_TYPE_FREETEXT", "freetext");
}
if (!defined("QUESTION_TYPE_SINGLE_CHOICE")) {
        define("QUESTION_TYPE_SINGLE_CHOICE", "single");
}
if (!defined("QUESTION_TYPE_MULTI_CHOICE")) {
        define("QUESTION_TYPE_MULTI_CHOICE", "multiple");
}

class Exam
{

        //
        // Get all exams that has been assigned to this user and is active right now.
        //
        public function getActiveExams($user)
        {
                $now = date('Y-m-d H:i:s');
                $sql = sprintf("SELECT  e.id AS ExamID,
                                        s.id AS StudentID,
                                        e.name AS ExamName,
                                        e.descr AS ExamDescription,
                                        e.starttime AS ExamStartTime,
                                        e.endtime AS ExamEndTime,
                                        e.created AS ExamCreated,
                                        e.updated AS ExamUpdated,
                                        e.creator AS ExamCreator
                                FROM    students s, exams e
                                WHERE   s.user = '%s' AND
                                        s.exam_id = e.id AND
                                        '%s' BETWEEN e.starttime AND e.endtime",
                                $user, $now);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        public function getFinishedExams($user)
        {
                $now = date('Y-m-d H:i:s');
                $sql = sprintf("SELECT  e.id AS ExamID,
                                        s.id AS StudentID,
                                        e.name AS ExamName,
                                        e.descr AS ExamDescription,
                                        e.starttime AS ExamStartTime,
                                        e.endtime AS ExamEndTime,
                                        e.created AS ExamCreated,
                                        e.updated AS ExamUpdated,
                                        e.creator AS ExamCreator,
                                        e.decoded AS ExamDecoded
                                FROM    students s, exams e
                                WHERE   s.user = '%s' AND
                                        s.exam_id = e.id AND
                                        '%s' >= e.endtime",
                                $user, $now);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get data for this exam.
        //
        public function getExamData($user, $exam)
        {
                $sql = sprintf("SELECT  e.id AS ExamID,
                                        s.id AS StudentID,
                                        e.name AS ExamName,
                                        e.descr AS ExamDescription,
                                        e.starttime AS ExamStartTime,
                                        e.endtime AS ExamEndTime,
                                        e.created AS ExamCreated,
                                        e.updated AS ExamUpdated,
                                        e.creator AS ExamCreator,
                                        e.decoded AS ExamDecoded,
                                        e.testcase AS ExamTestCase,
                                        e.lockdown AS ExamLockDown
                                FROM    students s, exams e
                                WHERE   s.user = '%s' AND
                                        s.exam_id = e.id AND
                                        e.id = %d", $user, $exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // Returns true if user has questions assigned on this exam.
        // 
        private function hasQuestions($exam, $user)
        {
                $sql = sprintf("SELECT  COUNT(*) AS num
                                        FROM    students s, answers a
                                        WHERE   s.id = a.student_id AND
                                                s.user = '%s' AND
                                                s.exam_id = %d", $user, $exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();
                return $row['num'] != 0;
        }

        //
        // Set questions for user on this exam. How this is done depends on the
        // type of examination.
        //
        private function setQuestions($exam, $user)
        {
                $db = Database::getConnection();

                if ($db->supports('transaction')) {
                        $db->beginTransaction();
                }

                $sql = sprintf("SELECT SUM(randomize) AS random
                                FROM    topics
                                WHERE   exam_id = %d", $exam);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                if ($row['random'] == 0) {
                        $sql = sprintf("INSERT INTO answers(question_id, student_id)
                                        SELECT  q.id, s.id
                                        FROM    questions q, students s, exams e
                                        WHERE   s.exam_id = e.id AND
                                                e.id = q.exam_id AND
                                                e.id = %d AND
                                                s.user = '%s'",
                                        $exam, $user);
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                throw new DatabaseException($res->getMessage());
                        }
                } else {
                        // 
                        // Get list of all topics for this exam. Loop thru the
                        // list of topics.
                        //
                        // a) If randomize == 0, then select all questions in 
                        //    this topic.
                        // b) If randomize != 0, then select randomize number
                        //    of questions from this topic.
                        //
                        $sql = sprintf("SELECT  t.*
                                        FROM    topics t, questions q
                                        WHERE   q.topic_id = t.id AND
                                                q.exam_id = %d
                                        GROUP BY t.id", $exam);
                        $res = $db->query($sql);
                        if (PEAR::isError($res)) {
                                throw new DatabaseException($res->getMessage());
                        }
                        $topics = $res->fetchAll();
                        foreach ($topics as $topic) {
                                if ($topic['randomize'] != 0) {
                                        //
                                        // Using INSERT INTO with this select statement
                                        // flood the error log with 'Statement may not be safe
                                        // to log in statement format.'
                                        // 
                                        $sql = sprintf("SELECT  q.id AS qid, s.id AS sid
                                                        FROM    questions q, students s, exams e
                                                        WHERE   q.exam_id = %d AND
                                                                q.topic_id = %d AND
                                                                q.exam_id = e.id AND
                                                                e.id = s.exam_id AND
                                                                s.user = '%s'
                                                        ORDER BY RAND() LIMIT %d",
                                                        $exam,
                                                        $topic['id'],
                                                        $user,
                                                        $topic['randomize']);
                                        $res = $db->query($sql);
                                        if (PEAR::isError($res)) {
                                                throw new DatabaseException($res->getMessage());
                                        }

                                        //
                                        // Loop thru all qid/sid pairs:
                                        //
                                        $arr = array();
                                        while ($row = $res->fetchRow()) {
                                                $arr[] = sprintf("%d, %d", $row['qid'], $row['sid']);
                                        }

                                        //
                                        // Insert dynamic question bindings for this user:
                                        //
                                        $sql = sprintf("INSERT INTO answers(question_id, student_id) 
                                                        VALUES(%s)", implode("),(", $arr));
                                        $res = $db->query($sql);
                                        if (PEAR::isError($res)) {
                                                throw new DatabaseException($res->getMessage());
                                        }
                                } else {
                                        // 
                                        // This is almost trivial compared to the randomize case:
                                        //
                                        $sql = sprintf("INSERT INTO answers(question_id, student_id)
                                                        SELECT  q.id, s.id
                                                        FROM    questions q, students s, exams e
                                                        WHERE   q.exam_id = %d AND
                                                                q.topic_id = %d AND
                                                                q.exam_id = e.id AND
                                                                e.id = s.exam_id AND
                                                                s.user = '%s'",
                                                        $exam,
                                                        $topic['id'],
                                                        $user);
                                        $res = $db->query($sql);
                                        if (PEAR::isError($res)) {
                                                throw new DatabaseException($res->getMessage());
                                        }
                                }
                        }
                }
                if ($db->in_transaction) {
                        $db->commit();
                }
        }

        //
        // Get all active questions for this exam. If user is set, then the
        // set of questions assigned to the user account is returned.
        //
        // If the user has no questions assigned yet, then a set of questions
        // will be assigned.
        //
        public function getQuestions($exam, $user = null)
        {
                if (isset($user)) {
                        //
                        // Create question set for user if missing.
                        // 
                        if (!self::hasQuestions($exam, $user)) {
                                self::setQuestions($exam, $user);
                        }
                        $sql = sprintf("SELECT  q.id AS QuestionID,
                                                q.exam_id AS ExamID,
                                                q.score AS QuestionScore,
                                                q.name AS QuestionName,
                                                q.quest AS QuestionText,
                                                q.user AS QuestionPublisher,
                                                q.video AS QuestionVideo,
                                                q.image AS QuestionImage,
                                                q.audio AS QuestionAudio,
                                                q.type AS QuestionType,
                                                a.answered AS QuestionAnswered
                                        FROM    questions q, answers a, students s
                                        WHERE   q.exam_id = %d AND
                                                q.status = 'active' AND
                                                q.id = a.question_id AND
                                                s.id = a.student_id AND
                                                s.user = '%s'
                                        ORDER BY QuestionID", $exam, $user);
                } else {
                        $sql = sprintf("SELECT  q.id AS QuestionID,
                                                q.exam_id AS ExamID,
                                                q.score AS QuestionScore,
                                                q.name AS QuestionName,
                                                q.quest AS QuestionText,
                                                q.user AS QuestionPublisher,
                                                q.video AS QuestionVideo,
                                                q.image AS QuestionImage,
                                                q.audio AS QuestionAudio,
                                                q.type AS QuestionType
                                        FROM    questions q
                                        WHERE   q.exam_id = %d AND
                                                q.status = 'active'
                                        ORDER BY QuestionID", $exam);
                }
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get data for an single question.
        //
        public function getQuestionData($question)
        {
                $sql = sprintf("SELECT  id AS QuestionID,
                                        exam_id AS ExamID,
                                        topic_id AS TopicID,
                                        score AS QuestionScore,
                                        name AS QuestionName,
                                        quest AS QuestionText,
                                        user AS QuestionPublisher,
                                        video AS QuestionVideo,
                                        image AS QuestionImage,
                                        audio AS QuestionAudio,
                                        type AS QuestionType,
                                        status AS QuestionStatus
                                FROM    questions
                                WHERE   id = %d", $question);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // Get all topics in this exam.
        //
        public function getTopics($exam)
        {
                $sql = sprintf("SELECT  id AS TopicID,
                                        exam_id AS ExamID,
                                        name AS TopicName,
                                        randomize AS TopicRandom
                                FROM    topics
                                WHERE   exam_id = %d
                                ORDER BY TopicID", $exam);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get data for a single topic.
        //
        public function getTopicData($topic)
        {
                $sql = sprintf("SELECT  id AS TopicID,
                                        exam_id AS ExamID,
                                        name AS TopicName,
                                        randomize AS TopicRandom
                                FROM    topics
                                WHERE   id = %d", $topic);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // Parse serialized encoded options from string and return as hashed array.
        // This function can be called with the question text for an mulitple or
        // single option question. It also parses the answer to those question types.
        //
        // If answers argument is true, then the returned array contain the correct
        // answers as values with the options as array keys.
        //
        public function getQuestionChoice($str, $answers = false)
        {
                $match = array();
                if (preg_match('/^(.*)\n({.*}).*$/msU', $str, $match)) {
                        array_shift($match);   // Remove all matched string
                        if ($answers) {
                                $match[0] = trim($match[0]);
                                $match[1] = (array) json_decode($match[1]);
                        } else {
                                $match[0] = trim($match[0]);
                                $match[1] = array_keys((array) json_decode($match[1]));
                        }
                } else {
                        if (strlen($str) > 0) {
                                $match[0] = null;
                                $match[1] = json_decode($str);
                        } else {
                                $match[0] = null;
                                $match[1] = array();
                        }
                }
                return $match;
        }

        //
        // Get all answers for the exam for this user.
        //
        public function getAnswers($exam, $user)
        {
                $sql = sprintf("SELECT  a.id AS AnswerID,
                                        a.question_id AS QuestionID,
                                        a.student_id AS StudentID,
                                        a.answer AS AnswerText
                                FROM    answers a, students s
                                WHERE   s.exam_id = %d AND
                                        a.student_id = s.id AND
                                        s.user = '%s'
                                ORDER BY QuestionID", $exam, $user);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Get users answer for a single question.
        //
        public function getAnswerData($question, $user)
        {
                $sql = sprintf("SELECT  a.id AS AnswerID,
                                        a.question_id AS QuestionID,
                                        a.student_id AS StudentID,
                                        a.answered AS Answered,
                                        a.answer AS AnswerText
                                FROM    answers a, students s
                                WHERE   a.question_id = %d AND
                                        a.student_id = s.id AND
                                        s.user = '%s'", $question, $user);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new DataRecord($res->fetchRow());
        }

        //
        // Update or insert the answer from user to this question.
        //
        public function setAnswer($exam, $question, $user, $answer)
        {
                $uid = self::getStudentID($exam, $user);
                $sql = sprintf("UPDATE  answers SET answer = '%s', answered = 'Y'
                                WHERE   question_id = %d AND
                                        student_id = %d",
                                $answer, $question, $uid);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        //
        // Get the ID of this student.
        //
        private function getStudentID($exam, $user)
        {
                $db = Database::getConnection();
                $db->setFetchMode(MDB2_FETCHMODE_ORDERED);

                $sql = sprintf("SELECT  id 
                                FROM    students
                                WHERE   user = '%s' AND
                                        exam_id = %d", $user, $exam);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
                return $row[0];
        }

}

?>
