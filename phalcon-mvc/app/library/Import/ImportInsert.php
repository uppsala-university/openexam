<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    ImportInsert.php
// Created: 2015-04-15 00:01:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Import;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception as ImportException;
use OpenExam\Library\Import\Import;
use OpenExam\Library\Import\ImportData;
use PDO;
use Phalcon\Db\Adapter\Pdo as DbAdapter;
use Phalcon\Mvc\User\Component;
use RuntimeException;

/**
 * Insert import data.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportInsert extends Component
{

        /**
         * The exam ID.
         * @var int 
         */
        protected $_exam;
        /**
         * Database connection.
         * @var DbAdapter 
         */
        protected $_pdo;

        /**
         * Constructor.
         * @param int $exam The exam ID.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
                $this->_pdo = $this->dbwrite;
        }

        /**
         * Get exam ID.
         * @return int
         */
        public function getExamID()
        {
                return $this->_exam;
        }

        /**
         * Insert import data.
         * @param ImportData $data The data to import.
         * @param int $filter Which sections to import.
         */
        public function insert($data, $filter = Import::OPENEXAM_IMPORT_INCLUDE_ALL)
        {
                try {
                        $this->_pdo->begin();
                        $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // 
                        // Detect present data:
                        // 
                        if (!isset($data->project)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_PROJECT;
                        }
                        if (!isset($data->roles)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_ROLES;
                        }
                        if (!isset($data->topics)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_TOPICS;
                        }
                        if (!isset($data->questions)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_QUESTIONS;
                        }
                        if (!isset($data->answers)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_ANSWERS;
                        }
                        if (!isset($data->results)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_RESULTS;
                        }
                        if (!isset($data->students)) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_STUDENTS;
                        }

                        // 
                        // Resolve dependencies:
                        // 
                        if (($filter & Import::OPENEXAM_IMPORT_INCLUDE_TOPICS) == 0) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_QUESTIONS;
                        }
                        if (($filter & Import::OPENEXAM_IMPORT_INCLUDE_QUESTIONS) == 0) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_ANSWERS;
                        }
                        if (($filter & Import::OPENEXAM_IMPORT_INCLUDE_ANSWERS) == 0) {
                                $filter &= ~Import::OPENEXAM_IMPORT_INCLUDE_RESULTS;
                        }

                        // 
                        // Start import:
                        // 
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_PROJECT) {
                                $this->insertProjectData($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_ROLES) {
                                $this->insertRoles($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_STUDENTS) {
                                $this->insertStudents($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_TOPICS) {
                                $this->insertTopics($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_QUESTIONS) {
                                $this->insertQuestions($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_ANSWERS) {
                                $this->insertAnswers($data);
                        }
                        if ($filter & Import::OPENEXAM_IMPORT_INCLUDE_RESULTS) {
                                $this->insertResults($data);
                        }

                        $this->_pdo->commit();
                } catch (Exception $exception) {
                        $this->_pdo->rollback();
                        throw $exception;
                }
        }

        /**
         * Insert project properties data.
         * @param ImportData $data The import data.
         */
        private function insertProjectData(&$data)
        {
                $data->project->name = sprintf("%s (Imported %s)", $data->project->name, strftime("%x %X"));
                //
                // Set project properties:
                //
                if ($this->_exam != 0) {
                        $sql = sprintf("UPDATE  exams SET
                                                name = '%s',
                                                description = '%s',
                                                orgunit = '%s',
                                                starttime = '%s',
                                                endtime = '%s',
                                                created = '%s',
                                                grades = '%s'
                                        WHERE   id = %d", $data->project->name, $data->project->description, $data->project->orgunit, $data->project->starttime, $data->project->endtime, $data->project->created, $data->project->grades, $this->_exam);
                        $this->insertData($sql);
                } else {
                        $sql = sprintf("INSERT INTO exams(name, descr, orgunit, starttime, endtime, created, creator, grades)
                                                        VALUES('%s','%s','%s','%s','%s','%s','%s','%s')", $data->project->name, $data->project->description, $data->project->orgunit, $data->project->starttime, $data->project->endtime, $data->project->created, $this->user->getPrincipalName(), $data->project->grades);
                        $this->_exam = $this->insertData($sql, "exams");
                }
        }

        /**
         * Insert roles.
         * @param ImportData $data The import data.
         */
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
                                                        VALUES(%d, '%s')", $table, $this->_exam, $user);
                                                        $this->insertData($sql);
                                                }
                                        }
                                }
                        }
                }
        }

        /**
         * Insert topics.
         * @param ImportData $data The import data.
         */
        private function insertTopics(&$data)
        {
                $data->map()->topics = array();

                if (isset($data->topics)) {
                        foreach ($data->topics->topic as $topic) {
                                $sql = sprintf("INSERT INTO topics(exam_id, name, randomize)
                                        VALUES(%d,'%s','%s')", $this->_exam, $topic->name, $topic->randomize);
                                $data->map()->topics[(int) $topic['id']] = $this->insertData($sql, "topics");
                        }
                }
        }

        /**
         * Create new topic.
         * @param ImportData $data The import data.
         */
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

        /**
         * Insert questions.
         * @param ImportData $data The import data.
         */
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
                                        VALUES(%d,%d,%F,'%s','%s','%s','%s','%s','%s','%s','%s','%s')", $this->_exam, $data->map()->topics[(int) $question['topic']], $question->score, $question->name, $question->quest, $question->publisher, $question->video, $question->image, $question->audio, $question->type, $question->status, $question->comment);
                                $data->map()->questions[(int) $question['id']] = $this->insertData($sql, "questions");
                        }
                }
        }

        /**
         * Insert students.
         * @param ImportData $data The import data.
         */
        private function insertStudents(&$data)
        {
                $data->map()->students = array();

                if (isset($data->students)) {
                        foreach ($data->students->student as $student) {
                                $sql = sprintf("INSERT INTO students(exam_id, user, code, tag)
                                        VALUES(%d,'%s','%s','%s')", $this->_exam, $student->user, $student->code, $student->tag);
                                $data->map()->students[(string) $student->user] = $this->insertData($sql, "students");
                        }
                }
        }

        /**
         * Insert answers.
         * 
         * Throws RuntimeException on unsupported version format. Throws ImportException
         * if trying to import answers without questions and/or students.
         * 
         * @param ImportData $data The import data.
         * @throws ImportException
         * @throws RuntimeException
         */
        private function insertAnswers(&$data)
        {
                $data->map()->answers = array();

                if (!isset($data->questions)) {
                        throw new ImportException("Can't import answers without questions.", Error::NOT_ACCEPTABLE);
                }
                if (!isset($data->students)) {
                        throw new ImportException("Can't import answers without students.", Error::NOT_ACCEPTABLE);
                }

                if (isset($data->answers)) {
                        foreach ($data->answers->answer as $answer) {
                                $answer->answer = $this->db->escape($answer->text);
                                if ($data['format'] == 6071 || $data['format'] == 6072) {
                                        $sql = sprintf("INSERT INTO answers(question_id, student_id, answered, answer, comment)
                                                        VALUES(%d,%d,'Y','%s','%s')", $data->map()->questions[(int) $answer['question']], $data->map()->students[(string) $answer['user']], $answer->answer, $answer->acomment);
                                        $data->map()->answers[(int) $answer['id']] = $this->insertData($sql, "answers");
                                } elseif ($data['format'] == 6073) {
                                        $sql = sprintf("INSERT INTO answers(question_id, student_id, answered, answer, comment)
                                                        VALUES(%d,%d,'Y','%s','%s')", $data->map()->questions[(int) $answer['question']], $data->map()->students[(string) $answer['user']], $answer->answer, $answer->comment);
                                        $data->map()->answers[(int) $answer['id']] = $this->insertData($sql, "answers");
                                } else {
                                        throw new RuntimeException(sprintf("FORMAT: %d\n", $data->format));
                                }
                        }
                }
        }

        /**
         * Insert results.
         * 
         * Throws ImportException if trying to import result without existing
         * answers.
         * 
         * @param ImportData $data The import data.
         * @throws ImportException
         */
        private function insertResults(&$data)
        {
                $data->map()->results = array();

                if (!isset($data->answers)) {
                        throw new ImportException("Can't import results without anwers.", Error::NOT_ACCEPTABLE);
                }

                if (isset($data->results)) {
                        foreach ($data->results->result as $result) {
                                $result->comment = $this->db->escape($result->comment);
                                $sql = sprintf("INSERT INTO results(answer_id, score, comment)
                                                VALUES(%d,%f,'%s')", $data->map()->answers[(int) $result['answer']], $result->score, $result->comment);
                                $data->map()->results[(int) $result['id']] = $this->insertData($sql, "results");
                        }
                }
        }

        /**
         * Write data to database.
         * 
         * The data (SQL) is automatic escaped. Returns the ID of last insert 
         * ID on affected table.
         * 
         * @param string $sql The SQL statement.
         * @param string $table The affected table.
         * @return int
         */
        private function insertData($sql, $table = null)
        {
                printf(__METHOD__ . " SQL: %s'\n", $sql);
                $sql = $this->_pdo->escapeString($sql);
                $res = $this->_pdo->execute($sql);
                return $this->_pdo->lastInsertID($table);
        }

}
