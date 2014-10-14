<?php

// 
// Copyright (C) 2010-2013 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/teacher/correct.inc
// Author: Anders Lövgren
// Date:   2010-04-28
// 
// Support for correcting exams.
// 

class Examinator
{

        private $exam;

        public function __construct($exam)
        {
                $this->exam = $exam;
        }

        //
        // Get all exams where this user is a examinator.
        //
        public static function getExams($user, $order = "starttime", $collate = "DESC")
        {
                $sql = sprintf("SELECT  e.id AS ExamID,
                                        e.name AS ExamName,
                                        e.descr AS ExamDescription,
                                        e.starttime AS ExamStartTime,
                                        e.endtime AS ExamEndTime,
                                        e.created AS ExamCreated,
                                        e.updated AS ExamUpdated,
                                        e.creator AS ExamCreator
			FROM exams e, examinators x
			WHERE e.id = x.exam_id AND x.user = '%s'
			ORDER BY e.%s %s", $user, $order, $collate);
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        private function getStudentCode($user)
        {
                return strtoupper(substr(md5(sprintf("%s%d", $user, time())), 0, 8));
        }

        //
        // Add a single student to this exam.
        //
        public function addStudent($user, $code)
        {
                if (!isset($code) || strlen($code) == 0) {
                        $code = $this->getStudentCode($user);
                }
                $sql = sprintf("INSERT INTO students(exam_id, user, code)
	                  VALUES(%d, '%s', '%s')", $this->exam, $user, $code);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        //
        // Add a list of students at once. The list is an hashed array where the key
        // is the username and the value is the anonymous code.
        //
        public function addStudents(&$data)
        {
                $sql = "INSERT INTO students(exam_id, user, code) VALUES";
                foreach ($data as $user => $code) {
                        if (!isset($code) || strlen($code) == 0) {
                                $code = $this->getStudentCode($user);
                        }
                        $sql .= sprintf("(%d, '%s', '%s'),", $this->exam, $user, $code);
                }
                $sql = substr($sql, 0, -1);

                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        //
        // Delete this student from the exam. The user argument is the numeric ID
        // of the student.
        //
        public function removeStudent($user)
        {
                $sql = sprintf("DELETE FROM students WHERE exam_id = %d AND id = %d", $this->exam, $user);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        //
        // Get all students for this exam. Remember to not expose the student username
        // unless the exam has been decoded.
        //
        public function getStudents()
        {
                $sql = sprintf("SELECT  id AS StudentID,
                                        exam_id AS ExamID,
                                        user AS StudentUser,
                                        code AS StudentCode
                                FROM students
                                WHERE exam_id = %d
                                ORDER BY StudentUser", $this->exam);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                return new ResultSet($res);
        }

        //
        // Sets the start and end time for this exam
        //
        public function setSchedule($stime, $etime)
        {
                if ($stime == 0 || $etime == 0) {
                        return;         // refuse
                }
                $sql = sprintf("UPDATE exams SET starttime = '%s', endtime = '%s' WHERE id = %d", date('Y-m-d H:i:s', $stime), date('Y-m-d H:i:s', $etime), $this->exam);

                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

}

?>
