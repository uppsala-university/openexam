<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Store.php
// Created: 2017-12-18 13:23:45
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Result;

use Exception;
use OpenExam\Library\Core\Error;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;

/**
 * Abstract base class for exam result.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class Store extends Component
{

        /**
         * @var Exam 
         */
        protected $_exam;

        /**
         * Constructor.
         * @param int|Exam $eid The exam.
         * @throws ModelException
         */
        public function __construct($eid)
        {
                if (!is_numeric($eid)) {
                        $this->_exam = $eid;
                } elseif (!($this->_exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find target exam.", Error::PRECONDITION_FAILED);
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_exam);
        }

        /**
         * Force generate files even if existing.
         * @var bool 
         */
        private $_forced = false;

        /**
         * Check if forced file generation is enabled.
         * @return boolean
         */
        public function getForced()
        {
                return $this->_forced;
        }

        /**
         * Force generate files even if existing.
         * @param bool $enable
         */
        public function setForced($enable = true)
        {
                $this->_forced = $enable;
        }

        /**
         * Check if caller can access student result.
         * 
         * This method modifies the argument if unset and caller is found
         * in the students collection on this exam.
         * 
         * @param int $sid The student ID.
         * @return boolean
         */
        public function canAccess(&$sid)
        {
                // 
                // Admins and staff can always access student result.
                // 
                if ($this->user->roles->isAdmin() ||
                    $this->user->roles->isStaff($this->_exam->id)) {
                        return true;
                }

                // 
                // Find student in students on this exam.
                // 
                if (!$sid) {
                        $found = $this->_exam->students->filter(function($student) {
                                if ($student->user == $this->user->getPrincipalName()) {
                                        return $student;
                                }
                        });
                } else {
                        $found = $this->_exam->students->filter(function($student) use($sid) {
                                if ($student->id == $sid) {
                                        return $student;
                                }
                        });
                }

                // 
                // Check filtered students result:
                // 
                if (count($found) != 1) {
                        return false;
                }
                if ($found[0]->user != $this->user->getPrincipalName()) {
                        return false;
                }

                if (!isset($sid)) {
                        $sid = $found[0]->id;
                }

                return true;
        }

        /**
         * Check if student has result file.
         * @param Student $student The student model.
         */
        public function hasFile($student)
        {
                return file_exists(self::getPath($this->_exam->id, $student->id, 'pdf'));
        }

        /**
         * Check if result directory exists for this exam.
         * @return bool
         */
        public function exist()
        {
                return file_exists(self::getPath($this->_exam->id));
        }

        /**
         * Cleanup result for this exam.
         */
        protected function clean()
        {
                foreach ($this->_exam->students as $student) {
                        $this->delete($student);
                }

                $target = sprintf("%s.zip", self::getPath($this->_exam->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new Exception("Failed unlink result archive.");
                        }
                }
                unset($target);

                $target = sprintf("%s.xls", self::getPath($this->_exam->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new Exception("Failed unlink result spreadsheet.");
                        }
                }
                unset($target);

                $target = sprintf("%s", self::getPath($this->_exam->id));
                if (file_exists($target)) {
                        if (!rmdir($target)) {
                                throw new Exception("Failed delete result directory.");
                        }
                }
                unset($target);
        }

        /**
         * Get directory path.
         * 
         * @param int $eid The exam ID.
         * @param int $sid The student ID.
         * @return string
         */
        protected function getPath($eid = 0, $sid = 0, $ext = null)
        {
                if ($eid == 0) {
                        return sprintf("%s/result", $this->config->application->cacheDir);
                } elseif ($sid == 0) {
                        return sprintf("%s/result/%d", $this->config->application->cacheDir, $eid);
                } elseif (!isset($ext)) {
                        return sprintf("%s/result/%d/%d", $this->config->application->cacheDir, $eid, $sid);
                } else {
                        return sprintf("%s/result/%d/%d.%s", $this->config->application->cacheDir, $eid, $sid, $ext);
                }
        }

        /**
         * Get student model.
         * 
         * @param int $sid The student ID.
         * @return Student
         * @throws ModelException
         */
        protected function getStudent($sid)
        {
                if (!($student = Student::findFirst($sid))) {
                        throw new ModelException("Failed find student.", Error::PRECONDITION_FAILED);
                }

                return $student;
        }

        /**
         * Delete file associated with this student.
         * @param int|Student $sid The student.
         */
        public abstract function delete($sid);

        /**
         * Create PDF for this student.
         * 
         * @param int|Student $sid The student ID or model.
         * @return boolean True if new file was created.
         * @throws Exception
         */
        public abstract function createFile($sid);

        /**
         * Create all PDF files in this exam.
         */
        public abstract function createFiles();

        /**
         * Create zip-file of exam results.
         */
        public abstract function createArchive();

        /**
         * Create archives for all exams where endtime is after date and
         * decoded status is true.
         * 
         * @param string|int $date The start date.
         * @throws ModelException
         */
        public abstract static function createArchives($date);
}
